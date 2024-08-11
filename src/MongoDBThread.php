<?php

declare(strict_types=1);

namespace Echore\AsyncMongo;

use Echore\AsyncMongo\inbound\MongoExecutionError;
use Echore\AsyncMongo\inbound\MongoExecutionOK;
use Echore\AsyncMongo\inbound\MongoInboundQueue;
use Echore\AsyncMongo\inbound\SessionStoreIdManager;
use Echore\AsyncMongo\operation\executable\MongoExecutableOperation;
use Echore\AsyncMongo\operation\MongoDumpMemoryOperation;
use Echore\AsyncMongo\operation\MongoOperation;
use Echore\AsyncMongo\outbound\MongoOutboundQueue;
use MongoDB\Client;
use pocketmine\MemoryManager;
use pocketmine\snooze\SleeperHandlerEntry;
use pocketmine\thread\Thread;
use PrefixedLogger;
use RuntimeException;
use SimpleLogger;
use stdClass;
use Throwable;

class MongoDBThread extends Thread {

	protected SleeperHandlerEntry $sleeperEntry;

	protected MongoInboundQueue $inbound;

	protected MongoOutboundQueue $outbound;

	protected string $uriOptions;

	protected string $driverOptions;

	protected bool $busy;

	public function __construct(
		SleeperHandlerEntry                    $sleeperEntry,
		MongoInboundQueue                      $inbound,
		MongoOutboundQueue                     $outbound,
		private readonly ?string               $uri,
		array                                  $uriOptions,
		array                                  $driverOptions,
		private readonly SessionStoreIdManager $sessionStoreIdManager,
		private readonly int                   $threadNumber
	) {
		$this->sleeperEntry = $sleeperEntry;
		$this->inbound = $inbound;
		$this->outbound = $outbound;
		$this->uriOptions = serialize($uriOptions);
		$this->driverOptions = serialize($driverOptions);
		$this->busy = false;
	}

	/**
	 * @return int
	 */
	public function getThreadNumber(): int {
		return $this->threadNumber;
	}

	/**
	 * @return bool
	 */
	public function isBusy(): bool {
		return $this->busy;
	}

	public function quitGracefully(): void {
		$this->outbound->dispose();
		$this->quit();
	}

	protected function onRun(): void {
		require_once __DIR__ . '/../vendor/autoload.php';

		$client = new Client($this->uri, unserialize($this->uriOptions), unserialize($this->driverOptions));
		$connector = new MongoDBConnector($client, $this->sessionStoreIdManager, $this->threadNumber);
		$notifier = $this->sleeperEntry->createNotifier();

		$logger = new PrefixedLogger(new SimpleLogger(), "AsyncMongoDB#$this->threadNumber");

		while (!$this->isKilled) {
			$raw = $this->outbound->fetchOperation($this->threadNumber);

			if ($raw === null) {
				break;
			}

			$op = unserialize($raw, ["allowed_classes" => true]);

			/**
			 * @var MongoOperation $op
			 */

			if ($op instanceof MongoExecutableOperation) {
				$connector->mongoizeOptions($op);
			}

			$this->busy = true;
			if ($op instanceof MongoDumpMemoryOperation) {
				$std = new stdClass();
				$std->thread = $this;
				$std->client = $client;
				$std->connector = $connector;
				MemoryManager::dumpMemory($std, $op->getOutputFolder(), $op->getMaxNesting(), $op->getMaxStringSize(), $logger);
			}

			$result = $connector->operate($op);

			if ($result instanceof Throwable) {
				$executionResult = new MongoExecutionError(new RuntimeException($result->getMessage(), $result->getCode()), $op);
			} else {
				$executionResult = new MongoExecutionOK($op->processResult($result), $op);
			}

			$this->busy = false;

			$this->inbound->sendExecutionResult($op->getTrackId(), $executionResult);

			$notifier->wakeupSleeper();
		}
	}
}
