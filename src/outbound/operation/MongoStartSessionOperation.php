<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\outbound\operation;

use Echore\AsyncMongo\inbound\result\IMongoResult;
use Echore\AsyncMongo\inbound\result\MongoSessionResult;
use Echore\AsyncMongo\inbound\SessionMediator;

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
