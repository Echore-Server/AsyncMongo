<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\result;

use MongoDB\DeleteResult;
use MongoDB\Exception\BadMethodCallException;
use ReflectionClass;

class MongoDeleteResult implements IMongoResult {
	/** @var SerializableWriteResult */
	private SerializableWriteResult $writeResult;

	/** @var boolean */
	private bool $isAcknowledged;

	public function __construct(SerializableWriteResult $writeResult) {
		$this->writeResult = $writeResult;
		$this->isAcknowledged = $writeResult->isAcknowledged();
	}

	/**
	 * Return whether this delete was acknowledged by the server.
	 *
	 * If the delete was not acknowledged, other fields from the WriteResult
	 * (e.g. deletedCount) will be undefined.
	 *
	 * @return boolean
	 */
	public function isAcknowledged() {
		return $this->isAcknowledged;
	}

	public static function from(DeleteResult $result): self {
		$writeResult = (new ReflectionClass($result))->getProperty("writeResult")->getValue($result);

		return new self(
			SerializableWriteResult::from($writeResult)
		);
	}

	public function onWakeup(): void {
	}

	/**
	 * Return the number of documents that were deleted.
	 *
	 * This method should only be called if the write was acknowledged.
	 *
	 * @return integer|null
	 * @throws BadMethodCallException if the write result is unacknowledged
	 * @see DeleteResult::isAcknowledged()
	 */
	public function getDeletedCount() {
		if ($this->isAcknowledged) {
			return $this->writeResult->getDeletedCount();
		}

		throw BadMethodCallException::unacknowledgedWriteResultAccess(__METHOD__);
	}


}
