<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\outbound\operation;

use Echore\AsyncMongo\inbound\SessionMediator;

interface ISessionHolder {

	public function getSessionNullable(): ?SessionMediator;

	public function getSessionNotNull(): SessionMediator;
}
