<?php

declare(strict_types=1);

namespace Echore\AsyncMongo;

use Echore\AsyncMongo\inbound\MongoExecutionError;
use Echore\AsyncMongo\inbound\MongoExecutionOK;
use Echore\AsyncMongo\operation\executable\MongoCountDocumentsOperation;
use Echore\AsyncMongo\operation\executable\MongoDeleteManyOperation;
use Echore\AsyncMongo\operation\executable\MongoDeleteOneOperation;
use Echore\AsyncMongo\operation\executable\MongoExecutableOperation;
use Echore\AsyncMongo\operation\executable\MongoFindOneOperation;
use Echore\AsyncMongo\operation\executable\MongoFindOperation;
use Echore\AsyncMongo\operation\executable\MongoInsertManyOperation;
use Echore\AsyncMongo\operation\executable\MongoInsertOneOperation;
use Echore\AsyncMongo\operation\executable\MongoReplaceOneOperation;
use Echore\AsyncMongo\operation\executable\MongoUpdateManyOperation;
use Echore\AsyncMongo\operation\executable\MongoUpdateOneOperation;
use Echore\AsyncMongo\operation\ISessionHolder;
use Echore\AsyncMongo\operation\MongoOperation;
use Echore\AsyncMongo\operation\MongoStartSessionOperation;
use Echore\AsyncMongo\operation\MongoSyncSessionOperation;
use Echore\AsyncMongo\result\IMongoResult;
use Echore\AsyncMongo\result\MongoSessionResult;
use Echore\AsyncMongo\session\SessionMediator;
use InvalidArgumentException;
use LogicException;
use pocketmine\Server;
use pocketmine\snooze\SleeperHandlerEntry;
use RuntimeException;
use Throwable;

class AsyncMongoDB {

	protected SleeperHandlerEntry $sleeperEntry;

	protected MongoDBThreadPool $threadPool;

	/**
	 * @var array<int, callable<MongoExecutionOK|MongoExecutionError>>
	 */
	protected array $handlers;

	protected int $nextTrackId;

	public function __construct(
		private readonly Server  $server,
		int                      $poolLimit,
		private readonly ?string $uri,
		private readonly array   $uriOptions,
		private readonly array   $driverOptions
	) {
		$this->nextTrackId = 0;
		$this->sleeperEntry = $server->getTickSleeper()->addNotifier(function(): void {
			assert($this->threadPool instanceof MongoDBThreadPool);
			foreach ($this->threadPool->fetchAllResult() as [$trackId, $result]) {
				/**
				 * @var (MongoExecutionOK|MongoExecutionError) $result
				 */

				if (!isset($this->handlers[$trackId])) {
					throw new RuntimeException("Handler not found for trackId $trackId");
				}

				($this->handlers[$trackId])($result, $trackId);
				unset($this->handlers[$trackId]);
			}
		});
		$this->threadPool = new MongoDBThreadPool(
			$this->sleeperEntry,
			$server->getLogger(),
			$poolLimit,
			$this->uri,
			$this->uriOptions,
			$this->driverOptions
		);
	}

	public function close(): void {
		$this->threadPool->quitGracefully();
	}

	/**
	 * @param string $databaseName
	 * @param string $collectionName
	 * @param $document
	 * @param array $options
	 * @return MongoInsertOneOperation
	 */
	public function insertOne(
		string $databaseName,
		string $collectionName,
		       $document,
		array  $options = []
	): MongoInsertOneOperation {
		return $this->track(new MongoInsertOneOperation($databaseName, $collectionName, $document, $options));
	}

	/**
	 * @template T of MongoOperation
	 * @param T $operation
	 * @param callable|null $overrideOnSuccess
	 * @param callable|null $overrideCatchError
	 * @param int|null $channel
	 * @return T
	 */
	public function track(mixed $operation, ?callable $overrideOnSuccess = null, ?callable $overrideCatchError = null, ?int $channel = null): mixed {
		if ($operation->getTrackId() !== null) {
			throw new RuntimeException("Operation({$operation->getTrackId()}) is already tracked");
		}

		$operation->setTrackId($this->nextTrackId++);

		$operation->makeSchedulable(function(?callable $onSuccess, ?callable $catchError = null) use ($operation, $overrideCatchError, $overrideOnSuccess, $channel): void {
			$this->handlers[$operation->getTrackId()] = function(MongoExecutionOK|MongoExecutionError $executionResult, int $trackId) use ($operation, $onSuccess, $catchError, $overrideOnSuccess, $overrideCatchError): void {
				if ($executionResult instanceof MongoExecutionOK) {
					if ($onSuccess !== null && $overrideOnSuccess === null) {
						$onSuccess($executionResult->getResult(), $trackId);
					}

					if ($overrideOnSuccess !== null) {
						$overrideOnSuccess($executionResult->getResult(), $trackId, $onSuccess);
					}
				} else {
					if ($catchError === null && $overrideCatchError === null) {
						$e = $executionResult->getException();
						throw new MongoDBException("Mongo driver exception: " . $e->getMessage(), $e->getCode(), $e);
					}

					if ($catchError !== null && $overrideCatchError === null) {
						$catchError($executionResult->getException(), $trackId);
					}

					if ($overrideCatchError !== null) {
						$overrideCatchError($executionResult->getException(), $trackId, $catchError);
					}
				}
			};

			if ($operation instanceof ISessionHolder && $operation->getSessionNullable() !== null) {
				$channel = $this->threadPool->getSessionStoreIdManager()->inquireThreadNumberFor($operation->getSessionNotNull()->getStoreId());
			}

			$this->threadPool->schedule($operation, $channel);

			$this->server->getLogger()->debug("Operation scheduled (trackId: {$operation->getTrackId()}, channel: $channel)");
		});

		return $operation;
	}

	/**
	 * @param string $databaseName
	 * @param string $collectionName
	 * @param array $documents
	 * @param array $options
	 * @return MongoInsertManyOperation
	 */
	public function insertMany(
		string $databaseName,
		string $collectionName,
		array  $documents,
		array  $options = []
	): MongoInsertManyOperation {
		return $this->track(new MongoInsertManyOperation($databaseName, $collectionName, $documents, $options));
	}

	public function transaction(callable $observer, callable $handler, MongoExecutableOperation ...$transactions): void {
		if (count($transactions) === 0) {
			throw new InvalidArgumentException("Specify at least one operation");
		}

		$this->startSession()->schedule(function(MongoSessionResult $result) use ($observer, $handler, $transactions): void {
			$session = $result->getSession();
			$session->start()->sync(function() use ($observer, $transactions, $session, $handler): void {
				$successResults = [];
				$errorResults = [];
				$count = 0;
				$transactionCount = count($transactions);

				$finalize = function() use ($observer, &$successResults, &$errorResults, $session, $handler): void {
					($observer)($successResults, $errorResults, $session);

					$session->sync(fn() => $session->end()->sync($handler));
				};

				foreach ($transactions as $k => $op) {
					$op->schedule(
						function(IMongoResult $result) use ($k, &$count, &$successResults, $transactionCount, $finalize): void {
							$successResults[$k] = $result;
							$count++;

							if ($count === $transactionCount) {
								$finalize();
							}
						},
						function(Throwable $e) use ($k, &$count, &$errorResults, $transactionCount, $finalize): void {
							$errorResults[$k] = $e;
							$count++;

							if ($count === $transactionCount) {
								$finalize();
							}
						}
					);
				}
			});
		});
	}

	/**
	 * @param array $options
	 * @return MongoStartSessionOperation
	 *
	 * Warning: Transaction feature requires replica set
	 */
	public function startSession(
		array $options = []
	): MongoStartSessionOperation {
		return $this->track(new MongoStartSessionOperation($options), function(MongoSessionResult $result, int $trackId, ?callable $original): void {
			$result->getSession()->makeSyncable(function(?callable $onSync) use ($result): void {
				$this->syncSessionState($result->getSession())->schedule($onSync);
			});

			if ($original !== null) {
				$original($result, $trackId);
			}
		});
	}

	/**
	 * @param SessionMediator $sessionMediator
	 * @return MongoSyncSessionOperation
	 */
	public function syncSessionState(
		SessionMediator $sessionMediator
	): MongoSyncSessionOperation {
		return $this->track(new MongoSyncSessionOperation($sessionMediator), function(MongoSessionResult $result, int $trackId, ?callable $original) use ($sessionMediator): void {
			$sessionMediator->makeSyncable(function(?callable $onSync) use ($result, $sessionMediator): void {
				$this->syncSessionState($sessionMediator)->schedule($onSync);
			});

			$sessionMediator->updateStatus($result->getSession()->getInboundStatus());

			if ($original !== null) {
				$original($result, $trackId);
			}
		});
	}

	public function sequentially(callable $handler, MongoOperation ...$operations): void {
		$successResults = [];
		$errorResults = [];

		$this->runSequentially(function() use (&$successResults, &$errorResults, $handler): void {
			($handler)($successResults, $errorResults);
		}, $operations, 0, $successResults, $errorResults);
	}

	private function runSequentially(callable $handler, array $operations, int $index, array &$successResults, array &$errorResults, bool $needsTrack = false): void {
		$op = array_values($operations)[$index] ?? null;

		if ($op === null) {
			($handler)();

			return;
		}

		$key = array_search($op, $operations, true);

		if ($key === false) {
			throw new LogicException("array_search() returned false");
		}

		if ($needsTrack) {
			$op = $this->track($op);
		}

		$op->schedule(
			function(IMongoResult $result, int $trackId) use (&$successResults, $handler, $operations, $index, &$errorResults, $key): void {
				$successResults[$key] = $result;
				$this->runSequentially($handler, $operations, $index + 1, $successResults, $errorResults);
			},
			function(Throwable $e, int $trackId) use (&$successResults, $handler, $operations, $index, &$errorResults, $key): void {
				$errorResults[$key] = $e;
				$this->runSequentially($handler, $operations, $index + 1, $successResults, $errorResults);
			},
		);
	}

	public function collection(string $databaseName, string $collectionName): DelegateCollection {
		return new DelegateCollection($this, $databaseName, $collectionName);
	}

	/**
	 * @param string $databaseName
	 * @param string $collectionName
	 * @param $filter
	 * @param array $options
	 * @return MongoFindOperation
	 */
	public function find(
		string $databaseName,
		string $collectionName,
		       $filter,
		array  $options = []
	): MongoFindOperation {
		return $this->track(new MongoFindOperation($databaseName, $collectionName, $filter, $options));
	}

	/**
	 * @param string $databaseName
	 * @param string $collectionName
	 * @param $filter
	 * @param array $options
	 * @return MongoFindOneOperation
	 */
	public function findOne(
		string $databaseName,
		string $collectionName,
		       $filter,
		array  $options = []
	): MongoFindOneOperation {
		return $this->track(new MongoFindOneOperation($databaseName, $collectionName, $filter, $options));
	}

	/**
	 * @param string $databaseName
	 * @param string $collectionName
	 * @param $filter
	 * @param array $options
	 * @return MongoCountDocumentsOperation
	 */
	public function countDocuments(
		string $databaseName,
		string $collectionName,
		       $filter,
		array  $options = []
	): MongoCountDocumentsOperation {
		return $this->track(new MongoCountDocumentsOperation($databaseName, $collectionName, $filter, $options));
	}

	/**
	 * @param string $databaseName
	 * @param string $collectionName
	 * @param $filter
	 * @param array $options
	 * @return MongoDeleteOneOperation
	 */
	public function deleteOne(
		string $databaseName,
		string $collectionName,
		       $filter,
		array  $options = []
	): MongoDeleteOneOperation {
		return $this->track(
			new MongoDeleteOneOperation(
				$databaseName,
				$collectionName,
				$filter,
				$options
			)
		);
	}

	/**
	 * @param string $databaseName
	 * @param string $collectionName
	 * @param $filter
	 * @param array $options
	 * @return MongoDeleteManyOperation
	 */
	public function deleteMany(
		string $databaseName,
		string $collectionName,
		       $filter,
		array  $options = []
	): MongoDeleteManyOperation {
		return $this->track(new MongoDeleteManyOperation($databaseName, $collectionName, $filter, $options));
	}

	/**
	 * @param string $databaseName
	 * @param string $collectionName
	 * @param $filter
	 * @param $replacement
	 * @param array $options
	 * @return MongoReplaceOneOperation
	 */
	public function replaceOne(
		string $databaseName,
		string $collectionName,
		       $filter,
		       $replacement,
		array  $options = []
	): MongoReplaceOneOperation {
		return $this->track(new MongoReplaceOneOperation($databaseName, $collectionName, $filter, $replacement, $options));
	}

	/**
	 * @param string $databaseName
	 * @param string $collectionName
	 * @param $filter
	 * @param $update
	 * @param array $options
	 * @return MongoUpdateOneOperation
	 */
	public function updateOne(
		string $databaseName,
		string $collectionName,
		       $filter,
		       $update,
		array  $options = []
	): MongoUpdateOneOperation {
		return $this->track(new MongoUpdateOneOperation($databaseName, $collectionName, $filter, $update, $options));
	}

	/**
	 * @param string $databaseName
	 * @param string $collectionName
	 * @param $filter
	 * @param $update
	 * @param array $options
	 * @return MongoUpdateManyOperation
	 */
	public function updateMany(
		string $databaseName,
		string $collectionName,
		       $filter,
		       $update,
		array  $options = []
	): MongoUpdateManyOperation {
		return $this->track(new MongoUpdateManyOperation($databaseName, $collectionName, $filter, $update, $options));
	}
}
