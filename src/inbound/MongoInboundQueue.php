<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\inbound;

use pmmp\thread\ThreadSafe;
use pmmp\thread\ThreadSafeArray;

class MongoInboundQueue extends ThreadSafe {

	/**
	 * @var ThreadSafeArray<array{0: int, 1: MongoExecutionError|MongoExecutionOK}>
	 */
	private ThreadSafeArray $queue;

	public function __construct() {
		$this->queue = new ThreadSafeArray();
	}

	public function sendExecutionResult(int $trackId, MongoExecutionOK|MongoExecutionError $result): void {
		$this->synchronized(function() use ($trackId, $result): void {
			$this->queue[] = serialize([$trackId, $result]);
			$this->notify();
		});
	}

	public function fetchResult(&$trackId, &$result): bool {
		return $this->synchronized(function() use (&$trackId, &$result): bool {
			while ($this->queue->count() === 0) {
				$this->wait();
			}

			return $this->shiftResult($trackId, $result);
		});
	}

	public function shiftResult(&$trackId, &$result): bool {
		$raw = $this->queue->shift();

		if ($raw === null) {
			return false;
		}

		[$trackId, $result] = unserialize($raw, ["allowed_classes" => true]);

		/**
		 * @var MongoExecutionOK|MongoExecutionError $result
		 */

		if ($result instanceof MongoExecutionOK) {
			$result->getResult()->onWakeup();
		}

		return true;
	}

	/**
	 * @return (array{0: int, 1: MongoExecutionError|MongoExecutionOK})[]
	 */
	public function fetchAllResult(): array {
		return $this->synchronized(function(): array {
			$results = [];

			while ($this->shiftResult($trackId, $result)) {
				$results[] = [$trackId, $result];
			}

			return $results;
		});
	}
}
