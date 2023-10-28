<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\operation\executable;

use Echore\AsyncMongo\operation\ISessionHolder;
use Echore\AsyncMongo\operation\MongoOperation;
use Echore\AsyncMongo\session\SessionMediator;
use MongoDB\Operation\Executable;
use RuntimeException;

abstract class MongoExecutableOperation extends MongoOperation implements ISessionHolder {

	protected string $databaseName;

	protected string $collectionName;

	protected array $options;

	protected ?SessionMediator $session;

	protected bool $syncSessionBefore;

	protected bool $syncSessionAfter;

	/**
	 * @param string $databaseName
	 * @param string $collectionName
	 * @param array $options
	 */
	public function __construct(string $databaseName, string $collectionName, array $options) {
		$this->databaseName = $databaseName;
		$this->collectionName = $collectionName;
		$this->options = $options;
		$this->session = null;
		$this->syncSessionBefore = false;
		$this->syncSessionAfter = false;
	}

	public function getSessionNullable(): ?SessionMediator {
		return $this->session;
	}

	public function getSessionNotNull(): SessionMediator {
		return $this->session ?? throw new RuntimeException("Session is null");
	}

	/**
	 * @param SessionMediator|null $session
	 * @return static
	 */
	public function setSession(?SessionMediator $session): static {
		$this->session = $session;

		return $this;
	}

	public function syncSessionBefore(bool $v = true): static {
		$this->syncSessionBefore = $v;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isSyncSessionAfter(): bool {
		return $this->syncSessionAfter;
	}

	/**
	 * @return bool
	 */
	public function isSyncSessionBefore(): bool {
		return $this->syncSessionBefore;
	}

	public function syncSessionAfter(bool $v = true): static {
		$this->syncSessionAfter = $v;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isScheduled(): bool {
		return $this->scheduled;
	}

	public function getDatabaseName(): string {
		return $this->databaseName;
	}

	public function getCollectionName(): string {
		return $this->collectionName;
	}

	public function getOptions(): array {
		return $this->options;
	}

	/**
	 * @param array $options
	 */
	public function setOptions(array $options): void {
		$this->options = $options;
	}

	public function __call(string $name, array $arguments) {
		if (in_array($name, $this->getFillableOptionKeys(), true)) {
			$this->options[$name] = $arguments[array_key_first($arguments)];

			return $this;
		}
	}

	/**
	 * @return string[]
	 */
	abstract public function getFillableOptionKeys(): array;

	/**
	 * @return Executable
	 * @internal
	 */
	abstract public function createExecutable(): mixed;

	abstract public function doesWrite(): bool;

	abstract public function doesRead(): bool;

	abstract public function doesParse(): bool;

}
