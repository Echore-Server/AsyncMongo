<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\operation;

use Echore\AsyncMongo\result\IMongoResult;
use Echore\AsyncMongo\result\MongoSessionResult;
use Echore\AsyncMongo\session\SessionMediator;

/**
 * @extends MongoOperation<MongoSessionResult>
 */
class MongoStartSessionOperation extends MongoOperation {

	private bool $startsTransaction;

	public function __construct(private readonly array $options) {
		$this->startsTransaction = false;
	}

	/**
	 * @return bool
	 */
	public function isStartsTransaction(): bool {
		return $this->startsTransaction;
	}
	public function startsTransaction(bool $startsTransaction = true): self {
		$this->startsTransaction = $startsTransaction;
		return $this;
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
