<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\outbound\operation\executable;

trait DocumentOperationTrait {
	public function __construct(string $databaseName, string $collectionName, private $document, array $options) {
		parent::__construct($databaseName, $collectionName, $options);
	}

	public function getDocument() {
		return $this->document;
	}
}
