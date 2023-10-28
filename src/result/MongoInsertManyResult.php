<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\result;

use MongoDB\Exception\BadMethodCallException;
use MongoDB\InsertManyResult;
use ReflectionClass;

class MongoInsertManyResult implements IMongoResult {

	/** @var SerializableWriteResult */
	private SerializableWriteResult $writeResult;

	/** @var array */
	private array $insertedIds;

	/** @var boolean */
	private bool $isAcknowledged;

	public function __construct(SerializableWriteResult $writeResult, array $insertedIds) {
		$this->writeResult = $writeResult;
		$this->insertedIds = $insertedIds;
		$this->isAcknowledged = $writeResult->isAcknowledged();
	}

	/**
	 * Return whether this insert result was acknowledged by the server.
	 *
	 * If the insert was not acknowledged, other fields from the WriteResult
	 * (e.g. insertedCount) will be undefined.
	 *
	 * @return boolean
	 */
	public function isAcknowledged() {
		return $this->writeResult->isAcknowledged();
	}

	public static function from(InsertManyResult $result): self {
		$writeResult = (new ReflectionClass($result))->getProperty("writeResult")->getValue($result);

		return new self(
			SerializableWriteResult::from($writeResult),
			$result->getInsertedIds()
		);
	}

	/**
	 * Return a map of the inserted documents' IDs.
	 *
	 * The index of each ID in the map corresponds to each document's position
	 * in the bulk operation. If a document had an ID prior to inserting (i.e.
	 * the driver did not generate an ID), the index will contain its "_id"
	 * field value. Any driver-generated ID will be a MongoDB\BSON\ObjectId
	 * instance.
	 *
	 * @return array
	 */
	public function getInsertedIds() {
		return $this->insertedIds;
	}

	public function onWakeup(): void {
	}

	/**
	 * Return the number of documents that were inserted.
	 *
	 * This method should only be called if the write was acknowledged.
	 *
	 * @return integer|null
	 * @throws BadMethodCallException if the write result is unacknowledged
	 * @see InsertManyResult::isAcknowledged()
	 */
	public function getInsertedCount() {
		if ($this->isAcknowledged) {
			return $this->writeResult->getInsertedCount();
		}

		throw BadMethodCallException::unacknowledgedWriteResultAccess(__METHOD__);
	}
}
