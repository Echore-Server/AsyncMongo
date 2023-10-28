<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\result;

interface IMongoResult {

	public function onWakeup(): void;

}
