<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\operation;

use Echore\AsyncMongo\session\SessionMediator;

interface ISessionHolder {

	public function getSessionNullable(): ?SessionMediator;

	public function getSessionNotNull(): SessionMediator;
}
