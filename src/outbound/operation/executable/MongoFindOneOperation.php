<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\outbound\operation\executable;

use Echore\AsyncMongo\inbound\result\IMongoResult;
use Echore\AsyncMongo\inbound\result\MongoDocumentResult;
use MongoDB\Operation\FindOne;

/**
 * @method self allowDiskUse(bool $v)
 * @method self allowPartialResults(bool $v)
 * @method self batchSize(int $size)
 * @method self collation($document)
 * @method self cursorType(int $type)
 * @method self hint(string|array|object $hint)
 * @method self max($document)
 * @method self maxAwaitTimeMS(int $ms)
 * @method self maxScan(int $max)
 * @method self maxTimeMS(int $ms)
 * @method self min($document)
 * @method self modifiers($document)
 * @method self noCursorTimeout(bool $v)
 * @method self oplogReplay(bool $v)
 * @method self projection($document)
 * @method self returnKey(bool $v)
 * @method self showRecordId(bool $v)
 * @method self skip(int $skip)
 * @method self snapshot(bool $v)
 * @method self sort($document)
 * @method self let($document)
 */
class MongoFindOneOperation extends MongoExecutableOperation {
	use FilterOperationTrait;

	public function createExecutable(): mixed {
		return new FindOne(
			$this->databaseName,
			$this->collectionName,
			$this->filter,
			$this->options
		);
	}

	public function getFillableOptionKeys(): array {
		return [
			"allowDiskUse",
			"allowPartialResults",
			"batchSize",
			"collation",
			"cursorType",
			"hint",
			"max",
			"maxAwaitTimeMS",
			"maxScan",
			"maxTimeMS",
			"min",
			"modifiers",
			"noCursorTimeout",
			"oplogReplay",
			"projection",
			"returnKey",
			"showRecordId",
			"skip",
			"snapshot",
			"sort",
			"let"
		];
	}

	public function processResult(mixed $result): IMongoResult {
		assert(is_array($result) || is_object($result) || is_null($result));

		return MongoDocumentResult::from($result);
	}

	public function doesWrite(): bool {
		return false;
	}

	public function doesRead(): bool {
		return true;
	}

	public function doesParse(): bool {
		return true;
	}
}
