<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\outbound\operation\executable;

use Echore\AsyncMongo\inbound\result\IMongoResult;
use Echore\AsyncMongo\inbound\result\MongoUpdateResult;
use MongoDB\Operation\UpdateOne;
use MongoDB\UpdateResult;

class MongoUpdateOneOperation extends MongoExecutableOperation {
	use UpdateOperationTrait;

	public function __construct(string $databaseName, string $collectionName, private $filter, private $update, array $options) {
		parent::__construct($databaseName, $collectionName, $options);
	}

	public function createExecutable(): UpdateOne {
		return new UpdateOne(
			$this->databaseName,
			$this->collectionName,
			$this->filter,
			$this->update,
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
