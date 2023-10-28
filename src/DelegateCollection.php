<?php

declare(strict_types=1);

namespace Echore\AsyncMongo;

use Echore\AsyncMongo\operation\executable\MongoCountDocumentsOperation;
use Echore\AsyncMongo\operation\executable\MongoDeleteManyOperation;
use Echore\AsyncMongo\operation\executable\MongoDeleteOneOperation;
use Echore\AsyncMongo\operation\executable\MongoFindOneOperation;
use Echore\AsyncMongo\operation\executable\MongoFindOperation;
use Echore\AsyncMongo\operation\executable\MongoInsertManyOperation;
use Echore\AsyncMongo\operation\executable\MongoInsertOneOperation;
use Echore\AsyncMongo\operation\executable\MongoReplaceOneOperation;
use Echore\AsyncMongo\operation\executable\MongoUpdateManyOperation;
use Echore\AsyncMongo\operation\executable\MongoUpdateOneOperation;

class DelegateCollection {

	public function __construct(
		private readonly AsyncMongoDB $mongo,
		private readonly string       $databaseName,
		private readonly string       $collectionName
	) {
	}

	public function insertOne($document, array $options = []): MongoInsertOneOperation {
		return $this->mongo->insertOne($this->databaseName, $this->collectionName, $document, $options);
	}

	public function insertMany($documents, array $options = []): MongoInsertManyOperation {
		return $this->mongo->insertMany($this->databaseName, $this->collectionName, $documents, $options);
	}

	public function find($filter, array $options = []): MongoFindOperation {
		return $this->mongo->find($this->databaseName, $this->collectionName, $filter, $options);
	}

	public function findOne($filter, array $options = []): MongoFindOneOperation {
		return $this->mongo->findOne($this->databaseName, $this->collectionName, $filter, $options);
	}

	public function deleteOne($filter, array $options = []): MongoDeleteOneOperation {
		return $this->mongo->deleteOne($this->databaseName, $this->collectionName, $filter, $options);
	}

	public function deleteMany($filter, array $options = []): MongoDeleteManyOperation {
		return $this->mongo->deleteMany($this->databaseName, $this->collectionName, $filter, $options);
	}

	public function countDocuments($filter, array $options = []): MongoCountDocumentsOperation {
		return $this->mongo->countDocuments($this->databaseName, $this->collectionName, $filter, $options);
	}

	public function replaceOne($filter, $replacement, array $options = []): MongoReplaceOneOperation {
		return $this->mongo->replaceOne($this->databaseName, $this->collectionName, $filter, $replacement, $options);
	}

	public function updateOne($filter, $update, array $options = []): MongoUpdateOneOperation {
		return $this->mongo->updateOne($this->databaseName, $this->collectionName, $filter, $update, $options);
	}

	public function updateMany($filter, $update, array $options = []): MongoUpdateManyOperation {
		return $this->mongo->updateMany($this->databaseName, $this->collectionName, $filter, $update, $options);
	}

}
