<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\outbound\operation\executable;
/**
 * @method self upsert(bool $v)
 * @method self arrayFilters(array $filters)
 * @method self bypassDocumentValidation(bool $v)
 * @method self collation($document)
 * @method self hint(string|array|object $hint)
 * @method self let($document)
 * @method self comment($comment)
 */
trait UpdateOperationTrait {

	public function getFillableOptionKeys(): array {
		return [
			"multi",
			"upsert",
			"arrayFilters",
			"bypassDocumentValidation",
			"collation",
			"hint",
			"let",
			"comment"
		];
	}
}
