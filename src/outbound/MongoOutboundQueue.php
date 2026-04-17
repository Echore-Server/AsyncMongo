<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\outbound;

use Echore\AsyncMongo\operation\MongoOperation;
use pmmp\thread\ThreadSafe;
use pmmp\thread\ThreadSafeArray;

class MongoOutboundQueue extends ThreadSafe {

	/** @var ThreadSafeArray<int, ThreadSafeArray<int, string>> */
	private ThreadSafeArray $byChannel;

	/** @var ThreadSafeArray<int, string> */
	private ThreadSafeArray $broadcast;

	private bool $disposed;

	public function __construct() {
		$this->byChannel = new ThreadSafeArray();
		$this->broadcast = new ThreadSafeArray();
		$this->disposed = false;
	}

	public function isDisposed(): bool {
		return $this->disposed;
	}

	public function dispose(): void {
		$this->disposed = true;
		$this->notify();
	}

	public function fetchOperation(int $channel, bool $onlyTargeted): ?string {
		return $this->synchronized(function() use ($onlyTargeted, $channel): ?string {
			while (!$this->disposed) {
				if (isset($this->byChannel[$channel])) {
					/** @var ThreadSafeArray $q */
					$q = $this->byChannel[$channel];
					if ($q->count() > 0) {
						return $q->shift();
					}
				}

				if (!$onlyTargeted && $this->broadcast->count() > 0) {
					return $this->broadcast->shift();
				}

				$this->wait();
			}

			return null;
		});
	}

	public function schedule(MongoOperation $operation, ?int $channel): void {
		$this->synchronized(function() use ($operation, $channel): void {
			$raw = serialize($operation);

			if ($channel === null) {
				$this->broadcast[] = $raw;
			} else {
				if (!isset($this->byChannel[$channel])) {
					$this->byChannel[$channel] = new ThreadSafeArray();
				}
				/** @var ThreadSafeArray $q */
				$q = $this->byChannel[$channel];
				$q[] = $raw;
			}

			$this->notify();
		});
	}
}
