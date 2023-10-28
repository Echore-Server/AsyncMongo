<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\inbound\result;

use MongoDB\Exception\BadMethodCallException;
use MongoDB\UpdateResult;
use ReflectionClass;

class MongoUpdateResult implements IMongoResult {

	private SerializableWriteResult $writeResult;

	private bool $isAcknowledged;

	public function __construct(SerializableWriteResult $writeResult) {
		$this->writeResult = $writeResult;
		$this->isAcknowledged = $writeResult->isAcknowledged();
	}

	/**
	 * Return whether this update was acknowledged by the server.
	 *
	 * If the update was not acknowledged, other fields from the WriteResult
	 * (e.g. matchedCount) will be undefined and their getter methods should not
	 * be invoked.
	 *
	 * @return boolean
	 */
	public function isAcknowledged(): bool {
		return $this->isAcknowledged;
	}

	public static function from(UpdateResult $result): self {
		$writeResult = (new ReflectionClass($result))->getProperty("writeResult")->getValue($result);

		return new self(
			SerializableWriteResult::from($writeResult)
		);
	}

	/**
	 * Return the number of documents that were matched by the filter.
	 *
	 * This method should only be called if the write was acknowledged.
	 *
	 * @return integer|null
	 * @throws BadMethodCallException if the write result is unacknowledged
	 * @see UpdateResult::isAcknowledged()
	 */
	public function getMatchedCount(): ?int {
		if ($this->isAcknowledged) {
			return $this->writeResult->getMatchedCount();
		}

		throw BadMethodCallException::unacknowledgedWriteResultAccess(__METHOD__);
	}

	public function onWakeup(): void {
	}

	/**
	 * Return the number of documents that were modified.
	 *
	 * This value is undefined (i.e. null) if the write executed as a legacy
	 * operation instead of command.
	 *
	 * This method should only be called if the write was acknowledged.
	 *
	 * @return integer|null
	 * @throws BadMethodCallException if the write result is unacknowledged
	 * @see UpdateResult::isAcknowledged()
	 */
	public function getModifiedCount(): ?int {
		if ($this->isAcknowledged) {
			return $this->writeResult->getModifiedCount();
		}

		throw BadMethodCallException::unacknowledgedWriteResultAccess(__METHOD__);
	}

	/**
	 * Return the number of documents that were upserted.
	 *
	 * This method should only be called if the write was acknowledged.
	 *
	 * @return integer|null
	 * @throws BadMethodCallException if the write result is unacknowledged
	 * @see UpdateResult::isAcknowledged()
	 */
	public function getUpsertedCount(): ?int {
		if ($this->isAcknowledged) {
			return $this->writeResult->getUpsertedCount();
		}

		throw BadMethodCallException::unacknowledgedWriteResultAccess(__METHOD__);
	}

	/**
	 * Return the ID of the document inserted by an upsert operation.
	 *
	 * If the document had an ID prior to upserting (i.e. the server did not
	 * need to generate an ID), this will contain its "_id". Any
	 * server-generated ID will be a MongoDB\BSON\ObjectId instance.
	 *
	 * This value is undefined (i.e. null) if an upsert did not take place.
	 *
	 * This method should only be called if the write was acknowledged.
	 *
	 * @return mixed|null
	 * @throws BadMethodCallException if the write result is unacknowledged
	 * @see UpdateResult::isAcknowledged()
	 */
	public function getUpsertedId(): mixed {
		if ($this->isAcknowledged) {
			foreach ($this->writeResult->getUpsertedIds() as $id) {
				return $id;
			}

			return null;
		}

		throw BadMethodCallException::unacknowledgedWriteResultAccess(__METHOD__);
	}
}
