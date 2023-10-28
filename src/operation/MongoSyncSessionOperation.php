<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\operation;

use Echore\AsyncMongo\result\IMongoResult;
use Echore\AsyncMongo\result\MongoSessionResult;
use Echore\AsyncMongo\session\SessionMediator;

class MongoSyncSessionOperation extends MongoOperation implements ISessionHolder {

	public function __construct(private readonly SessionMediator $session) {
	}

	public function getSessionNullable(): ?SessionMediator {
		return $this->session;
	}

	public function getSessionNotNull(): SessionMediator {
		return $this->session;
	}

	public function processResult(mixed $result): IMongoResult {
		assert($result instanceof SessionMediator);

		return new MongoSessionResult($result);
	}
}
