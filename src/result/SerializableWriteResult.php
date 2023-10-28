<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\result;

use MongoDB\Driver\WriteConcernError;
use MongoDB\Driver\WriteError;
use MongoDB\Driver\WriteResult;

class SerializableWriteResult {
	/**
	 * @var int|null
	 */
	private ?int $deletedCount;

	/**
	 * @var int|null
	 */
	private ?int $insertedCount;

	/**
	 * @var int|null
	 */
	private ?int $matchedCount;

	/**
	 * @var int|null
	 */
	private ?int $modifiedCount;

	/**
	 * @var int|null
	 */
	private ?int $upsertedCount;

	/**
	 * @var array
	 */
	private array $upsertedIds;

	/**
	 * @var WriteConcernError|null
	 */
	private ?WriteConcernError $writeConcernError;

	/**
	 * @var WriteError[]
	 */
	private array $writeErrors;

	private bool $acknowledged;

	/**
	 * @param int|null $deletedCount
	 * @param int|null $insertedCount
	 * @param int|null $matchedCount
	 * @param int|null $modifiedCount
	 * @param int|null $upsertedCount
	 * @param array $upsertedIds
	 * @param WriteConcernError|null $writeConcernError
	 * @param WriteError[] $writeErrors
	 * @param bool $acknowledged
	 */
	public function __construct(?int $deletedCount, ?int $insertedCount, ?int $matchedCount, ?int $modifiedCount, ?int $upsertedCount, array $upsertedIds, ?WriteConcernError $writeConcernError, array $writeErrors, bool $acknowledged) {
		$this->deletedCount = $deletedCount;
		$this->insertedCount = $insertedCount;
		$this->matchedCount = $matchedCount;
		$this->modifiedCount = $modifiedCount;
		$this->upsertedCount = $upsertedCount;
		$this->upsertedIds = $upsertedIds;
		$this->writeConcernError = $writeConcernError;
		$this->writeErrors = $writeErrors;
		$this->acknowledged = $acknowledged;
	}


	public static function from(WriteResult $writeResult): self {
		return new self(
			$writeResult->getDeletedCount(),
			$writeResult->getInsertedCount(),
			$writeResult->getMatchedCount(),
			$writeResult->getModifiedCount(),
			$writeResult->getUpsertedCount(),
			$writeResult->getUpsertedIds(),
			$writeResult->getWriteConcernError(),
			$writeResult->getWriteErrors(),
			$writeResult->isAcknowledged()
		);
	}

	public function getDeletedCount(): ?int {
		return $this->deletedCount;
	}

	public function getInsertedCount(): ?int {
		return $this->insertedCount;
	}

	public function getMatchedCount(): ?int {
		return $this->matchedCount;
	}

	public function getModifiedCount(): ?int {
		return $this->modifiedCount;
	}

	public function getUpsertedCount(): ?int {
		return $this->upsertedCount;
	}

	public function getUpsertedIds(): array {
		return $this->upsertedIds;
	}

	public function getWriteConcernError(): ?WriteConcernError {
		return $this->writeConcernError;
	}

	public function getWriteErrors(): array {
		return $this->writeErrors;
	}

	public function isAcknowledged(): bool {
		return $this->acknowledged;
	}

	public function __serialize(): array {
		return [
			"deletedCount"      => $this->deletedCount,
			"insertedCount"     => $this->insertedCount,
			"matchedCount"      => $this->matchedCount,
			"modifiedCount"     => $this->modifiedCount,
			"upsertedCount"     => $this->upsertedCount,
			"upsertedIds"       => serialize($this->upsertedIds),
			"writeConcernError" => $this->writeConcernError !== null ? serialize($this->writeConcernError) : null,
			"writeErrors"       => serialize($this->writeErrors),
			"acknowledged"      => $this->acknowledged
		];
	}

	public function __unserialize(array $data): void {
		$this->deletedCount = $data["deletedCount"];
		$this->insertedCount = $data["insertedCount"];
		$this->matchedCount = $data["matchedCount"];
		$this->modifiedCount = $data["modifiedCount"];
		$this->upsertedCount = $data["upsertedCount"];
		$this->upsertedIds = unserialize($data["upsertedIds"]);
		$this->writeConcernError = ($rawWriteConcernError = $data["writeConcernError"]) !== null ? unserialize($rawWriteConcernError) : null;
		$this->writeErrors = unserialize($data["writeErrors"]);
		$this->acknowledged = $data["acknowledged"];
	}
}
