<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\result;

class MongoThreadStatusResult implements IMongoResult {

	private int $memoryUsage;

	private int $realMemoryUsage;

	public function __construct(
		int $memoryUsage,
		int $realMemoryUsage
	) {
		$this->memoryUsage = $memoryUsage;
		$this->realMemoryUsage = $realMemoryUsage;
	}

	/**
	 * @return int
	 */
	public function getMemoryUsage(): int {
		return $this->memoryUsage;
	}

	/**
	 * @return int
	 */
	public function getRealMemoryUsage(): int {
		return $this->realMemoryUsage;
	}

	public function onWakeup(): void {
	}
}
