<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\inbound;

use Echore\AsyncMongo\inbound\result\IMongoResult;
use Echore\AsyncMongo\outbound\operation\MongoOperation;

/**
 * @template T of IMongoResult
 */
class MongoExecutionOK {

	/**
	 * @var T
	 */
	protected mixed $result;

	protected MongoOperation $operation;

	/**
	 * @param T $result
	 * @param MongoOperation $operation
	 */
	public function __construct(
		mixed          $result,
		MongoOperation $operation
	) {
		$this->result = $result;
		$this->operation = $operation;
	}

	/**
	 * @return MongoOperation
	 */
	public function getOperation(): MongoOperation {
		return $this->operation;
	}

	/**
	 * @return T
	 */
	public function getResult(): mixed {
		return $this->result;
	}
}
