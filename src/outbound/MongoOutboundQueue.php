<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\outbound;

use Echore\AsyncMongo\outbound\operation\MongoOperation;
use pmmp\thread\ThreadSafe;
use pmmp\thread\ThreadSafeArray;

class MongoOutboundQueue extends ThreadSafe {

	private ThreadSafeArray $scheduleByChannel;

	private ThreadSafeArray $schedule;

	private bool $disposed;

	public function __construct() {
		$this->scheduleByChannel = new ThreadSafeArray();
		$this->schedule = new ThreadSafeArray();
		$this->disposed = false;
	}

	/**
	 * @return bool
	 */
	public function isDisposed(): bool {
		return $this->disposed;
	}

	public function dispose(): void {
		$this->disposed = true;
	}

	public function fetchOperation(int $channel): ?string {
		return $this->synchronized(function() use ($channel): ?string {
			if (!isset($this->scheduleByChannel[$channel])) {
				$this->scheduleByChannel[$channel] = new ThreadSafeArray();
			}

			while (($this->scheduleByChannel[$channel]->count() === 0 && $this->schedule->count() === 0) && !$this->disposed) {
				$this->wait();
			}

			if ($this->scheduleByChannel[$channel]->count() > 0) {
				return $this->scheduleByChannel[$channel]->shift();
			} else {
				return $this->schedule->shift();
			}
		});
	}

	public function schedule(MongoOperation $operation, ?int $channel): void {
		$this->synchronized(function() use ($operation, $channel): void {
			if ($channel !== null) {
				if (!isset($this->scheduleByChannel[$channel])) {
					$this->scheduleByChannel[$channel] = new ThreadSafeArray();
				}

				$this->scheduleByChannel[$channel][] = serialize($operation);
			} else {
				$this->schedule[] = serialize($operation);
			}
			$this->notify();
		});
	}
}
