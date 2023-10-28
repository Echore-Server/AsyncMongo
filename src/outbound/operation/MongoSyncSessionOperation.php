<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\outbound\operation;

use Echore\AsyncMongo\inbound\result\IMongoResult;
use Echore\AsyncMongo\inbound\result\MongoSessionResult;
use Echore\AsyncMongo\inbound\SessionMediator;

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
