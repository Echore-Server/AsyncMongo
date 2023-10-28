<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\inbound;

use pmmp\thread\ThreadSafe;
use pmmp\thread\ThreadSafeArray;
use RuntimeException;

class SessionStoreIdManager extends ThreadSafe {

	private int $nextStoreId;

	private ThreadSafeArray $storeIdMap;

	public function __construct() {
		$this->nextStoreId = 0;
		$this->storeIdMap = new ThreadSafeArray();
	}

	public function reportThreadNumber(int $storeId, int $threadNumber): void {
		$this->synchronized(function() use ($storeId, $threadNumber): void {
			$this->storeIdMap[$storeId] = $threadNumber;
		});
	}

	public function inquireThreadNumberFor(int $storeId): int {
		return $this->synchronized(function() use ($storeId): int {
			return $this->storeIdMap[$storeId] ?? throw new RuntimeException("Unknown store id $storeId");
		});
	}

	public function nextStoreId(): int {
		return $this->synchronized(function(): int {
			$this->nextStoreId++;

			return $this->nextStoreId;
		});
	}
}
