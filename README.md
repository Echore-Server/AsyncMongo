## Usage

### Connect to server

```php
use Echore\AsyncMongo\AsyncMongoDB;
use pocketmine\Server;

/**
 * @var Server $pmmpServer
 */

$mongo = new AsyncMongoDB(
    $pmmpServer,
    $poolLimit, // max count of threads
    null, // server uri, nullable (uses default uri)
    [], // uri options,
    [] // mongodb driver options
);
```

### InsertOne

```php
use Echore\AsyncMongo\AsyncMongoDB;use Echore\AsyncMongo\result\MongoInsertOneResult;

/**
 * @var AsyncMongoDB $mongo
 */

$mongo->insertOne(
    "databaseName",
    "collectionName",
    $document,
    $options
)->schedule(
    function(MongoInsertOneResult $result): void{
        echo "Inserted {$result->getInsertedCount()}!" . PHP_EOL;
        var_dump($result);
    }
    function(Throwable $e): void{
        echo "Error occurred: {$e->getMessage()}" . PHP_EOL;
    }
);
```
