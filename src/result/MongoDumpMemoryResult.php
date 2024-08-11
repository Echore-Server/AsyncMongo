<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\result;

class MongoDumpMemoryResult implements IMongoResult {

	public function onWakeup(): void {
	}
}
