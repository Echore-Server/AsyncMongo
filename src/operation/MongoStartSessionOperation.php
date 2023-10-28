<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\operation;

use Echore\AsyncMongo\result\IMongoResult;
use Echore\AsyncMongo\result\MongoSessionResult;
use Echore\AsyncMongo\session\SessionMediator;

class MongoStartSessionOperation extends MongoOperation {

	public function __construct(private readonly array $options) {
	}

	/**
	 * @return array
	 */
	public function getOptions(): array {
		return $this->options;
	}

	public function processResult(mixed $result): IMongoResult {
		assert($result instanceof SessionMediator);

		return new MongoSessionResult($result);
	}
}
