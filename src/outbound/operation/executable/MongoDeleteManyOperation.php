<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\outbound\operation\executable;

use Echore\AsyncMongo\inbound\result\IMongoResult;
use Echore\AsyncMongo\inbound\result\MongoDeleteResult;
use MongoDB\DeleteResult;
use MongoDB\Operation\DeleteMany;

/**
 * @method self collation($document)
 * @method self hint(string|array|object $hint)
 * @method self let($document)
 * @method self comment($comment)
 */
class MongoDeleteManyOperation extends MongoExecutableOperation {
	use FilterOperationTrait;

	public function createExecutable(): DeleteMany {
		return new DeleteMany(
			$this->databaseName,
			$this->collectionName,
			$this->filter,
			$this->options
		);
	}

	public function getFillableOptionKeys(): array {
		return [
			"collation",
			"hint",
			"let",
			"comment"
		];
	}

	public function processResult(mixed $result): IMongoResult {
		assert($result instanceof DeleteResult);

		return MongoDeleteResult::from($result);
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
