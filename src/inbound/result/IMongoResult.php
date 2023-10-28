<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\inbound\result;

interface IMongoResult {

	public function onWakeup(): void;

}
