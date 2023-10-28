<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\outbound\operation\executable;

trait FilterOperationTrait {

	public function __construct(string $databaseName, string $collectionName, private $filter, array $options = []) {
		parent::__construct($databaseName, $collectionName, $options);
	}

	public function getFilter() {
		return $this->filter;
	}

}
