<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\operation;

use Echore\AsyncMongo\result\IMongoResult;
use Echore\AsyncMongo\result\MongoThreadStatusResult;

class MongoFetchThreadStatusOperation extends MongoOperation {

	public function processResult(mixed $result): IMongoResult {
		assert(is_array($result));

		return new MongoThreadStatusResult(
			$result[0],
			$result[1]
		);
	}
}
