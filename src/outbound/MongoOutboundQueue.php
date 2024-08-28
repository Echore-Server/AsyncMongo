<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\outbound;

use Echore\AsyncMongo\operation\MongoOperation;
use pmmp\thread\ThreadSafe;
use pmmp\thread\ThreadSafeArray;

class MongoOutboundQueue extends ThreadSafe {

	private ThreadSafeArray $schedule;

	private ThreadSafeArray $channel;

	private bool $disposed;

	private int $latestOffset;

	private int $currentOffset;

	public function __construct() {
		$this->schedule = new ThreadSafeArray();
		$this->channel = new ThreadSafeArray();
		$this->disposed = false;
		$this->latestOffset = 0;
		$this->currentOffset = 0;
	}

	/**
	 * @return bool
	 */
	public function isDisposed(): bool {
		return $this->disposed;
	}

	public function dispose(): void {
		$this->disposed = true;
		$this->notify();
	}

	public function fetchOperation(int $channel): ?string {
		return $this->synchronized(function() use ($channel): ?string {
			while (($this->schedule->count() === 0 || ($this->channel[$this->currentOffset] !== null && $this->channel[$this->currentOffset] !== $channel)) && !$this->disposed) {
				$this->wait();
			}

			$this->currentOffset++;
			$this->channel->shift();

			return $this->schedule->shift();
		});
	}

	public function schedule(MongoOperation $operation, ?int $channel): void {
		$this->synchronized(function() use ($operation, $channel): void {
			$this->schedule[++$this->latestOffset] = serialize($operation);
			$this->channel[$this->latestOffset] = $channel;
			$this->notify();
		});
	}
}
