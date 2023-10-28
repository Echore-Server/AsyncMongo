<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\operation\executable;

use Echore\AsyncMongo\result\IMongoResult;
use Echore\AsyncMongo\result\MongoUpdateResult;
use MongoDB\Operation\ReplaceOne;
use MongoDB\UpdateResult;

class MongoReplaceOneOperation extends MongoExecutableOperation {
	use UpdateOperationTrait;

	public function __construct(string $databaseName, string $collectionName, private $filter, private $replacement, array $options) {
		parent::__construct($databaseName, $collectionName, $options);
	}

	public function createExecutable(): ReplaceOne {
		return new ReplaceOne(
			$this->databaseName,
			$this->collectionName,
			$this->filter,
			$this->replacement,
			$this->options
		);
	}

	public function processResult(mixed $result): IMongoResult {
		assert($result instanceof UpdateResult);

		return MongoUpdateResult::from($result);
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
