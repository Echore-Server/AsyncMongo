<?php

declare(strict_types=1);

namespace Echore\AsyncMongo;

use Echore\AsyncMongo\inbound\SessionMediator;
use Echore\AsyncMongo\inbound\SessionStoreIdManager;
use Echore\AsyncMongo\outbound\operation\executable\MongoExecutableOperation;
use Echore\AsyncMongo\outbound\operation\MongoOperation;
use Echore\AsyncMongo\outbound\operation\MongoStartSessionOperation;
use Echore\AsyncMongo\outbound\operation\MongoSyncSessionOperation;
use MongoDB\Client;
use MongoDB\Driver\Session;
use RuntimeException;
use Throwable;
use function MongoDB\is_in_transaction;
use function MongoDB\select_server;

class MongoDBConnector {

	private readonly Client $client;

	/**
	 * @var Session[]
	 */
	private array $sessions;

	private SessionStoreIdManager $storeIdManager;

	public function __construct(Client $client, SessionStoreIdManager $storeIdManager, private readonly int $threadNumber) {
		$this->client = $client;
		$this->sessions = [];
		$this->storeIdManager = $storeIdManager;
	}

	/**
	 * @return Client|null
	 */
	public function getClient(): ?Client {
		return $this->client;
	}

	public function mongoizeOptions(MongoExecutableOperation $operation): void {
		$options = $operation->getOptions();

		if ($operation->doesWrite()) {
			if (!isset($options['writeConcern']) && !is_in_transaction($options)) {
				$options['writeConcern'] = $this->client->getWriteConcern();
			}
		}

		if ($operation->doesRead()) {
			if (!isset($options['readPreference']) && !is_in_transaction($options)) {
				$options['readPreference'] = $this->client->getReadPreference();
			}

			if (!isset($options['readConcern']) && !is_in_transaction($options)) {
				$options['readConcern'] = $this->client->getReadConcern();
			}
		}

		if ($operation->doesParse()) {
			if (!isset($options['typeMap'])) {
				//$options['typeMap'] = $this->client->getTypeMap();
				// fixme: generates PHP Incomplete class
			}
		}

		$operation->setOptions($options);
	}

	public function operate(MongoOperation $operation): mixed {
		if ($operation instanceof MongoExecutableOperation) {
			return $this->execute($operation);
		} else {
			return $this->handleOperation($operation);
		}
	}

	public function execute(MongoExecutableOperation $operation): mixed {
		$isInTransaction = $operation->getSessionNullable() !== null;
		if ($isInTransaction) {
			$options = $operation->getOptions();
			$session = $this->getSessionNotNull($operation->getSessionNotNull()->getStoreId());
			$options["session"] = $session;
			$operation->setOptions($options);

			if ($operation->isSyncSessionBefore()) {
				$this->observeSessionMediator($operation->getSessionNullable() ?? throw new RuntimeException("syncSessionBefore option is set, but session is null"));
			}
		}

		try {
			$server = select_server($this->client->getManager(), $operation->getOptions());

			$res = $operation->createExecutable()->execute($server);
		} catch (Throwable $e) {
			$res = $e;
		} finally {
			if ($isInTransaction) {
				unset($options["session"]); // Session is not serializable!

				$operation->setOptions($options);

				if ($operation->isSyncSessionAfter()) {
						$this->observeSessionMediator($operation->getSessionNullable()) ?? throw new RuntimeException("syncSessionAfter option is set, but session is null");
				}
			}
		}


		return $res;
	}

	protected function getSessionNotNull(int $storeId): Session {
		return $this->sessions[$storeId] ?? throw new RuntimeException("Session store id $storeId not found");
	}

	protected function observeSessionMediator(SessionMediator $sessionMediator): void {
		if ($sessionMediator->getOutboundStatus() === SessionMediator::COMMIT) {
			$this->commitTransaction($sessionMediator);
		} elseif ($sessionMediator->getOutboundStatus() === SessionMediator::ABORT) {
			$this->abortTransaction($sessionMediator);
		} elseif ($sessionMediator->getOutboundStatus() === SessionMediator::END) {
			$this->endSession($sessionMediator);
		} elseif ($sessionMediator->getOutboundStatus() === SessionMediator::START) {
			$this->startTransaction($sessionMediator);
		}
	}

	private function commitTransaction(SessionMediator $sessionMediator): void {
		$session = $this->getSessionNotNull($sessionMediator->getStoreId());

		$session->commitTransaction();

		$sessionMediator->updateStatus(SessionMediator::COMMIT);
	}

	private function abortTransaction(SessionMediator $sessionMediator): void {
		$session = $this->getSessionNotNull($sessionMediator->getStoreId());

		$session->abortTransaction();

		$sessionMediator->updateStatus(SessionMediator::ABORT);
	}

	private function endSession(SessionMediator $sessionMediator): void {
		$session = $this->getSessionNotNull($sessionMediator->getStoreId());

		$session->endSession();

		$sessionMediator->updateStatus(SessionMediator::END);
	}

	private function startTransaction(SessionMediator $sessionMediator): void {
		$session = $this->getSessionNotNull($sessionMediator->getStoreId());

		$session->startTransaction();

		$sessionMediator->updateStatus(SessionMediator::START);
	}

	protected function handleOperation(MongoOperation $operation): mixed {
		assert(!$operation instanceof MongoExecutableOperation);

		if ($operation instanceof MongoStartSessionOperation) {
			$mediator = new SessionMediator($this->storeIdManager->nextStoreId());

			$this->sessions[$mediator->getStoreId()] = $this->client->startSession($operation->getOptions());
			$this->storeIdManager->reportThreadNumber($mediator->getStoreId(), $this->threadNumber);

			return $mediator;
		} elseif ($operation instanceof MongoSyncSessionOperation) {
			$this->observeSessionMediator($operation->getSessionNotNull());

			return $operation->getSessionNotNull();
		}

		return null;
	}
}
