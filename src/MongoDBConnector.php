<?php

declare(strict_types=1);

namespace Echore\AsyncMongo;

use Echore\AsyncMongo\inbound\SessionStoreIdManager;
use Echore\AsyncMongo\operation\executable\MongoExecutableOperation;
use Echore\AsyncMongo\operation\MongoDumpMemoryOperation;
use Echore\AsyncMongo\operation\MongoFetchThreadStatusOperation;
use Echore\AsyncMongo\operation\MongoOperation;
use Echore\AsyncMongo\operation\MongoStartSessionOperation;
use Echore\AsyncMongo\operation\MongoSyncSessionOperation;
use Echore\AsyncMongo\session\SessionMediator;
use MongoDB\Client;
use MongoDB\Driver\ReadConcern;
use MongoDB\Driver\Session;
use MongoDB\Driver\WriteConcern;
use RuntimeException;
use Throwable;
use function MongoDB\select_server;

class MongoDBConnector {

	private readonly Client $client;

	/**
	 * @var Session[]
	 */
	private array $sessions;

	private array $sessionOptions;

	private SessionStoreIdManager $storeIdManager;

	public function __construct(Client $client, SessionStoreIdManager $storeIdManager, private readonly int $threadNumber) {
		$this->client = $client;
		$this->sessions = [];
		$this->sessionOptions = [];
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

		$this->parseConcerns($options);

		if ($operation->doesWrite()) {
			if (!isset($options['writeConcern']) && $operation->getSessionNullable() === null) {
				$options['writeConcern'] = $this->client->getWriteConcern();
			}
		}

		if ($operation->doesRead()) {
			if (!isset($options['readPreference']) && $operation->getSessionNullable() === null) {
				$options['readPreference'] = $this->client->getReadPreference();
			}

			if (!isset($options['readConcern']) && $operation->getSessionNullable() === null) {
				$options['readConcern'] = $this->client->getReadConcern();
			}
		}

		if ($operation->doesParse()) {
			if (!isset($options['typeMap'])) {
				$options['typeMap'] = $this->client->getTypeMap();
			}
		}

		$operation->setOptions($options);
	}

	protected function parseConcerns(array &$options): void {
		if (isset($options["readConcern"]) && is_array($options["readConcern"])) {
			$options["readConcern"] = new ReadConcern($options["readConcern"]["level"]);
		}

		if (isset($options["writeConcern"]) && is_array($options["writeConcern"])) {
			$options["writeConcern"] = new WriteConcern($options["writeConcern"]["w"], $options["writeConcern"]["wtimeout"] ?? 0, $options["writeConcern"]["journal"] ?? false);
		}
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

		$session->startTransaction($this->sessionOptions[$sessionMediator->getStoreId()]);

		$sessionMediator->updateStatus(SessionMediator::START);

		unset($this->sessionOptions[$sessionMediator->getStoreId()]);
	}

	protected function handleOperation(MongoOperation $operation): mixed {
		assert(!$operation instanceof MongoExecutableOperation);

		if ($operation instanceof MongoStartSessionOperation) {
			$mediator = new SessionMediator($this->storeIdManager->nextStoreId());

			$options = $operation->getOptions();
			$this->parseConcerns($options);
			$this->sessions[$mediator->getStoreId()] = $this->client->startSession($options);
			$this->sessionOptions[$mediator->getStoreId()] = $options;
			$this->storeIdManager->reportThreadNumber($mediator->getStoreId(), $this->threadNumber);

			return $mediator;
		} elseif ($operation instanceof MongoSyncSessionOperation) {
			$this->observeSessionMediator($operation->getSessionNotNull());

			return $operation->getSessionNotNull();
		} elseif ($operation instanceof MongoFetchThreadStatusOperation) {
			$memoryUsage = memory_get_usage();
			$realMemoryUsage = memory_get_usage(true);

			return [$memoryUsage, $realMemoryUsage];
		} elseif ($operation instanceof MongoDumpMemoryOperation) {
			return null;
		}

		return null;
	}
}
