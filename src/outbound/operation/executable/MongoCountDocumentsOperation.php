<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\outbound\operation\executable;

use Echore\AsyncMongo\inbound\result\IMongoResult;
use Echore\AsyncMongo\inbound\result\MongoValueResult;
use MongoDB\Operation\CountDocuments;

/**
 * @method self limit(int $limit)
 * @method self skip(int $skip)
 * @method self collation($document)
 * @method self comment($comment)
 * @method self hint(string|array|object $hint)
 * @method self maxTimeMS(int $ms)
 * @method self useCursor(bool $v)
 * @method self allowDiskUse(bool $v)
 * @method self bypassDocumentValidation(bool $v)
 * @method self explain(bool $v)
 * @method self let($document)
 * @method self maxAwaitTimeMS(int $ms)
 */
class MongoCountDocumentsOperation extends MongoExecutableOperation {
	use FilterOperationTrait;

	public function createExecutable(): CountDocuments {
		return new CountDocuments(
			$this->databaseName,
			$this->collectionName,
			$this->filter,
			$this->options
		);
	}

	public function getFillableOptionKeys(): array {
		return [
			"limit",
			"skip",
			"collation",
			"comment",
			"hint",
			"maxTimeMS",
			"useCursor",
			"allowDiskUse",
			"batchSize",
			"bypassDocumentValidation",
			"explain",
			"let",
			"maxAwaitTimeMS"
		];
	}

	public function processResult(mixed $result): IMongoResult {
		assert(is_int($result));

		return new MongoValueResult($result);
	}

	public function doesWrite(): bool {
		return false;
	}

	public function doesRead(): bool {
		return true;
	}

	public function doesParse(): bool {
		return false;
	}
}
