<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\operation\executable;

use Echore\AsyncMongo\result\IMongoResult;
use Echore\AsyncMongo\result\MongoInsertOneResult;
use MongoDB\InsertOneResult;
use MongoDB\Operation\InsertOne;

/**
 * @method self bypassDocumentValidation(bool $v)
 */
class MongoInsertOneOperation extends MongoExecutableOperation {
	use DocumentOperationTrait;

	public function createExecutable(): mixed {
		return new InsertOne(
			$this->databaseName,
			$this->collectionName,
			$this->document,
			$this->options
		);
	}

	public function getFillableOptionKeys(): array {
		return ["bypassDocumentValidation"];
	}

	public function processResult(mixed $result): IMongoResult {
		assert($result instanceof InsertOneResult);

		return MongoInsertOneResult::from($result);
	}

	public function doesWrite(): bool {
		return true;
	}

	public function doesRead(): bool {
		return false;
	}

	public function doesParse(): bool {
		return false;
	}
}
