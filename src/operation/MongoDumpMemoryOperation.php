<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\operation;

use Echore\AsyncMongo\result\IMongoResult;
use Echore\AsyncMongo\result\MongoDumpMemoryResult;

class MongoDumpMemoryOperation extends MongoOperation {

	public function __construct(
		private readonly string $outputFolder,
		private readonly int    $maxNesting,
		private readonly int    $maxStringSize
	) {
	}

	/**
	 * @return string
	 */
	public function getOutputFolder(): string {
		return $this->outputFolder;
	}

	/**
	 * @return int
	 */
	public function getMaxNesting(): int {
		return $this->maxNesting;
	}

	/**
	 * @return int
	 */
	public function getMaxStringSize(): int {
		return $this->maxStringSize;
	}

	public function processResult(mixed $result): IMongoResult {
		return new MongoDumpMemoryResult();
	}
}
