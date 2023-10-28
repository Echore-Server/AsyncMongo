<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\result;

use RuntimeException;

class MongoValueResult implements IMongoResult {

	public function __construct(private readonly string|int|float|null|bool $value) {
	}

	public function getBool(): bool {
		if (!is_bool($this->value)) {
			throw new RuntimeException("Value is not bool");
		}

		return $this->value;
	}

	public function onWakeup(): void {
	}

	public function getInt(): int {
		if (!is_int($this->value)) {
			throw new RuntimeException("Value is not integer");
		}

		return $this->value;
	}

	public function getFloat(): float {
		if (!is_int($this->value) && !is_float($this->value)) {
			throw new RuntimeException("Value is not float or integer");
		}

		return $this->value;
	}

	public function getPositiveInt(): int {
		if (!is_int($this->value)) {
			throw new RuntimeException("Value is not integer");
		}

		if ($this->value < 0) {
			throw new RuntimeException("Value is negative");
		}

		return $this->value;
	}

	public function getString(): string {
		if (!is_string($this->value)) {
			throw new RuntimeException("Value is not string");
		}

		return $this->value;
	}

	public function isNull(): bool {
		return $this->value === null;
	}
}
