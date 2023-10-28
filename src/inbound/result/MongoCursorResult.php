<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\inbound\result;

use MongoDB\Driver\Cursor;
use MongoDB\Driver\CursorId;

class MongoCursorResult implements IMongoResult {

	private array $array;

	private bool $dead;

	private CursorId $id;

	public function __construct(array $array, bool $dead, CursorId $id) {
		$this->array = $array;
		$this->dead = $dead;
		$this->id = $id;
	}

	public static function from(Cursor $cursor): self {
		return new self(
			$cursor->toArray(),
			$cursor->isDead(),
			$cursor->getId()
		);
	}

	public function isDead(): bool {
		return $this->dead;
	}

	public function getId(): CursorId {
		return $this->id;
	}

	public function onWakeup(): void {
	}

	public function getArray(): array {
		return $this->array;
	}

	public function __serialize(): array {
		return [
			"array" => serialize($this->array),
			"dead"  => $this->dead,
			"id"    => $this->id
		];
	}

	public function __unserialize(array $data): void {
		$this->array = unserialize($data["array"]);
		$this->dead = $data["dead"];
		$this->id = $data["id"];
	}
}
