<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\result;

use Echore\AsyncMongo\session\SessionMediator;

class MongoSessionResult implements IMongoResult {

	private SessionMediator $session;

	public function __construct(SessionMediator $session) {
		$this->session = $session;
	}

	public function onWakeup(): void {
	}

	/**
	 * @return SessionMediator
	 */
	public function getSession(): SessionMediator {
		return $this->session;
	}
}
