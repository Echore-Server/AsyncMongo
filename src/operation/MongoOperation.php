<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\operation;

use Closure;
use Echore\AsyncMongo\result\IMongoResult;
use RuntimeException;

abstract class MongoOperation {

	protected ?int $trackId = null;

	protected ?Closure $scheduleCallback = null;

	protected bool $scheduled = false;


	/**
	 * @param callable $callback
	 * @return void
	 * @internal
	 */
	public function makeSchedulable(callable $callback): void {
		if ($this->scheduleCallback !== null) {
			throw new RuntimeException("Cannot schedule same operation instance");
		}

		if ($this->scheduled) {
			throw new RuntimeException("Cannot make schedulable: already scheduled");
		}

		$this->scheduleCallback = $callback(...);
	}

	public function schedule(?callable $onSuccess = null, ?callable $catchError = null): static {
		if ($this->scheduleCallback === null) {
			throw new RuntimeException("Cannot schedule: unknown schedule method");
		}

		if ($this->scheduled) {
			throw new RuntimeException("Already scheduled");
		}

		$callback = $this->scheduleCallback;
		$this->scheduleCallback = null;

		($callback)($onSuccess, $catchError);

		$this->scheduled = true;

		return $this;
	}


	/**
	 * @param mixed $result
	 * @return IMongoResult
	 * @internal
	 */
	abstract public function processResult(mixed $result): IMongoResult;

	/**
	 * @return int|null
	 */
	public function getTrackId(): ?int {
		return $this->trackId;
	}

	/**
	 * @param int $trackId
	 * @internal
	 */
	public function setTrackId(int $trackId): void {
		if ($this->trackId !== null) {
			throw new RuntimeException("Already set track id");
		}
		$this->trackId = $trackId;
	}
}
