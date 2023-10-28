<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\inbound;

use Closure;
use RuntimeException;

class SessionMediator {

	const COMMIT = 0;
	const ABORT = 1;
	const END = 2;
	const START = 3;

	private int $storeId;

	private bool $closed;

	private ?int $outboundStatus;

	private ?int $inboundStatus;

	private ?Closure $syncCallback;

	private bool $syncInProgress;

	public function __construct(int $storeId) {
		$this->storeId = $storeId;
		$this->closed = false;
		$this->outboundStatus = null;
		$this->inboundStatus = null;
		$this->syncCallback = null;
		$this->syncInProgress = false;
	}

	public function makeSyncable(callable $callback): void {
		if ($this->syncCallback !== null) {
			throw new RuntimeException("Already sync-able");
		}

		$this->syncCallback = $callback(...);
	}

	/**
	 * @param callable|null $onSync
	 * @param bool $force Sync forcefully
	 * @return $this
	 */
	public function sync(?callable $onSync = null, bool $force = false): static {
		if ($this->syncCallback === null) {
			throw new RuntimeException("Not sync-able");
		}

		if ($this->outboundStatus === null && !$force) {
			throw new RuntimeException("No update state to sync");
		}

		if ($this->syncInProgress) {
			throw new RuntimeException("Synchronization in progress");
		}

		$callback = $this->syncCallback;
		$this->syncCallback = null;
		$this->syncInProgress = true;

		($callback)($onSync);

		return $this;
	}

	public function __sleep(): array {
		return ["storeId", "closed", "outboundStatus", "inboundStatus", "syncInProgress"];
	}

	public function __wakeup(): void {
		$this->syncCallback = null;
	}

	/**
	 * @param int $inbound
	 * @return void
	 * @internal
	 */
	public function updateStatus(int $inbound): void {
		$this->inboundStatus = $inbound;
		$this->outboundStatus = null;
		$this->syncInProgress = false;
	}

	public function commit(): static {
		$this->checkOutboundUpdate();

		$this->outboundStatus = self::COMMIT;

		return $this;
	}

	private function checkOutboundUpdate(): void {
		if ($this->closed) {
			throw new RuntimeException("Cannot update: closed session");
		}

		if ($this->outboundStatus !== null) {
			throw new RuntimeException("Already set outbound state, please run sync() to sync state");
		}
	}

	public function abort(): static {
		$this->checkOutboundUpdate();

		$this->outboundStatus = self::ABORT;

		return $this;
	}

	public function end(): static {
		$this->checkOutboundUpdate();

		$this->outboundStatus = self::END;

		return $this;
	}

	public function start(): static {
		$this->checkOutboundUpdate();

		$this->outboundStatus = self::START;

		return $this;
	}

	/**
	 * @return int|null
	 */
	public function getInboundStatus(): ?int {
		return $this->inboundStatus;
	}

	/**
	 * @return int|null
	 */
	public function getOutboundStatus(): ?int {
		return $this->outboundStatus;
	}

	public function isCommitted(): bool {
		return $this->inboundStatus === self::COMMIT;
	}

	public function isEnded(): bool {
		return $this->inboundStatus === self::END;
	}

	public function isAborted(): bool {
		return $this->inboundStatus === self::ABORT;
	}

	/**
	 * @return bool
	 */
	public function isClosed(): bool {
		return $this->closed;
	}

	/**
	 * @param bool $closed
	 *
	 * @internal
	 */
	public function setClosed(bool $closed): void {
		$this->closed = $closed;
	}

	/**
	 * @return int
	 */
	public function getStoreId(): int {
		return $this->storeId;
	}
}
