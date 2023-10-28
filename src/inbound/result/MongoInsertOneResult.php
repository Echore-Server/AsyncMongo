<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\inbound\result;

use MongoDB\Exception\BadMethodCallException;
use MongoDB\InsertOneResult;
use ReflectionClass;

class MongoInsertOneResult implements IMongoResult {
	/** @var mixed */
	private $insertedId;

	/** @var boolean */
	private $isAcknowledged;

	private SerializableWriteResult $writeResult;

	public function __construct(SerializableWriteResult $writeResult, $insertedId) {
		$this->insertedId = $insertedId;
		$this->writeResult = $writeResult;
		$this->isAcknowledged = $writeResult->isAcknowledged();
	}

	/**
	 * @return bool
	 */
	public function isAcknowledged(): bool {
		return $this->isAcknowledged;
	}

	public static function from(InsertOneResult $result): self {
		$writeResult = (new ReflectionClass($result))->getProperty("writeResult")->getValue($result);

		return new self(
			SerializableWriteResult::from($writeResult),
			$result->getInsertedId()
		);
	}

	/**
	 * @return mixed
	 */
	public function getInsertedId(): mixed {
		return $this->insertedId;
	}

	public function onWakeup(): void {
	}

	/**
	 * @return int|null
	 */
	public function getInsertedCount(): ?int {
		if ($this->isAcknowledged) {
			return $this->writeResult->getInsertedCount();
		}

		throw BadMethodCallException::unacknowledgedWriteResultAccess(__METHOD__);
	}

	/**
	 * @return SerializableWriteResult
	 */
	public function getWriteResult(): SerializableWriteResult {
		return $this->writeResult;
	}
}
