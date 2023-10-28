<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\inbound;

use Echore\AsyncMongo\outbound\operation\MongoOperation;
use Throwable;

class MongoExecutionError {

	protected Throwable $exception;

	protected MongoOperation $operation;

	public function __construct(
		Throwable      $exception,
		MongoOperation $operation
	) {
		$this->exception = $exception;
		$this->operation = $operation;
	}

	/**
	 * @return MongoOperation
	 */
	public function getOperation(): MongoOperation {
		return $this->operation;
	}

	/**
	 * @return Throwable
	 */
	public function getException(): Throwable {
		return $this->exception;
	}
}
