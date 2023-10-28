<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\outbound\operation\executable;

use Echore\AsyncMongo\inbound\result\IMongoResult;
use Echore\AsyncMongo\inbound\result\MongoInsertManyResult;
use MongoDB\InsertManyResult;
use MongoDB\Operation\InsertMany;

/**
 * @method self ordered(bool $v = true)
 * @method self bypassDocumentValidation(bool $v = true)
 */
class MongoInsertManyOperation extends MongoExecutableOperation {

	public function __construct(string $databaseName, string $collectionName, private readonly array $documents, array $options) {
		parent::__construct($databaseName, $collectionName, $options);
	}

	public function createExecutable(): InsertMany {
		return new InsertMany($this->databaseName, $this->collectionName, $this->documents, $this->options);
	}

	public function getFillableOptionKeys(): array {
		return ["bypassDocumentValidation", "ordered"];
	}

	public function processResult(mixed $result): IMongoResult {
		assert($result instanceof InsertManyResult);

		return MongoInsertManyResult::from($result);
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
