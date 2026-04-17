<?php

declare(strict_types=1);

namespace Echore\AsyncMongo;

use RuntimeException;
use Throwable;

class MongoDBException extends RuntimeException {

	protected array $errorLabels;

	protected string $originalExceptionClass;

	protected ?string $previousExceptionClass;

	public function __construct(array $errorLabels, string $originalExceptionClass, ?string $previousExceptionClass, string $message = "", int $code = 0, ?Throwable $previous = null) {
		parent::__construct($message, $code, $previous);
		$this->errorLabels = $errorLabels;
		$this->originalExceptionClass = $originalExceptionClass;
		$this->previousExceptionClass = $previousExceptionClass;
	}
	
	public function getPreviousExceptionClass(): ?string {
		return $this->previousExceptionClass;
	}

	/**
	 * @return string
	 */
	public function getOriginalExceptionClass(): string {
		return $this->originalExceptionClass;
	}

	/**
	 * @return array
	 */
	public function getErrorLabels(): array {
		return $this->errorLabels;
	}

	public function hasErrorLabel(string $label): bool {
		return in_array($label, $this->errorLabels, true);
	}
}
