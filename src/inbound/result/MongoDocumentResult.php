<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\inbound\result;

class MongoDocumentResult implements IMongoResult {

	/**
	 * @var array|object|null
	 */
	private array|object|null $document;

	public function __construct($document) {
		$this->document = $document;
	}

	public static function from($document): self {
		return new self($document);
	}

	public function onWakeup(): void {
	}

	/**
	 * @return array|object|null
	 */
	public function getDocument(): null|object|array {
		return $this->document;
	}

	public function __serialize(): array {
		return [
			"document" => serialize($this->document)
		];
	}

	public function __unserialize(array $data): void {
		$this->document = unserialize($data["document"]);
	}
}
