<?php

declare(strict_types=1);

namespace Echore\AsyncMongo;

use Echore\AsyncMongo\inbound\MongoExecutionError;
use Echore\AsyncMongo\inbound\MongoExecutionOK;
use Echore\AsyncMongo\inbound\MongoInboundQueue;
use Echore\AsyncMongo\inbound\SessionStoreIdManager;
use Echore\AsyncMongo\operation\MongoOperation;
use Echore\AsyncMongo\outbound\MongoOutboundQueue;
use InvalidArgumentException;
use Logger;
use pmmp\thread\Thread as NativeThread;
use pocketmine\snooze\SleeperHandlerEntry;

class MongoDBThreadPool {

	protected SleeperHandlerEntry $sleeperEntry;

	/**
	 * @var array<int, MongoDBThread>
	 */
	protected array $threads;

	protected int $poolLimit;

	protected MongoInboundQueue $inboundQueue;

	protected MongoOutboundQueue $outboundQueue;

	protected SessionStoreIdManager $sessionStoreIdManager;

	public function __construct(
		SleeperHandlerEntry      $sleeperEntry,
		protected Logger         $logger,
		int                      $poolLimit,
		private readonly ?string $uri,
		private readonly array   $uriOptions,
		private readonly array   $driverOptions
	) {
		$this->sleeperEntry = $sleeperEntry;
		$this->threads = [];
		$this->poolLimit = $poolLimit;
		$this->inboundQueue = new MongoInboundQueue();
		$this->outboundQueue = new MongoOutboundQueue();
		$this->sessionStoreIdManager = new SessionStoreIdManager();

		$this->startThreads();
	}

	protected function startThreads(): void {
		for ($i = 0; $i < $this->poolLimit; $i++) {
			if (!isset($this->threads[$i])) {
				$this->threads[$i] = $thread = $this->createThread($i);
				$thread->start(NativeThread::INHERIT_INI);
				$this->logger->info("Started thread $i");
			}
		}
	}

	protected function createThread(int $number): MongoDBThread {
		return new MongoDBThread(
			$this->sleeperEntry,
			$this->inboundQueue,
			$this->outboundQueue,
			$this->uri,
			$this->uriOptions,
			$this->driverOptions,
			$this->sessionStoreIdManager,
			$number
		);
	}

	/**
	 * @return int
	 */
	public function getPoolLimit(): int {
		return $this->poolLimit;
	}

	/**
	 * @return int[]
	 */
	public function getThreadNumbers(): array {
		return array_keys($this->threads);
	}

	/**
	 * @return SessionStoreIdManager
	 */
	public function getSessionStoreIdManager(): SessionStoreIdManager {
		return $this->sessionStoreIdManager;
	}

	public function schedule(MongoOperation $operation, ?int $channel = null): void {
		if ($operation->getTrackId() === null) {
			throw new InvalidArgumentException("TrackId not set");
		}
		$this->updateThreadCountDynamically();

		$this->outboundQueue->schedule($operation, $channel);
	}

	protected function updateThreadCountDynamically(): void {
		// todo: implement
	}

	/**
	 * @return (array{0: int, 1: MongoExecutionError|MongoExecutionOK})[]
	 */
	public function fetchAllResult(): array {
		return $this->inboundQueue->fetchAllResult();
	}

	public function join(): void {
		foreach ($this->threads as $thread) {
			$thread->join();
		}
	}

	public function quitGracefully(): void {
		foreach ($this->threads as $thread) {
			$thread->quitGracefully();
		}
	}

	/**
	 * @return array<int, MongoDBThread>
	 */
	public function getThreads(): array {
		return $this->threads;
	}

	protected function getFineThreadChannel(): int {
		foreach ($this->threads as $thread) {
			if (!$thread->isBusy()) {
				return $thread->getThreadNumber();
			}
		}

		return 0; // number 0, sorry!
	}
}
