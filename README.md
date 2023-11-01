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
use Echore\AsyncMongo\AsyncMongoDB;
use Echore\AsyncMongo\result\MongoInsertOneResult;

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

### InsertMany

```php
use Echore\AsyncMongo\AsyncMongoDB;
use Echore\AsyncMongo\result\MongoInsertManyResult;use Echore\AsyncMongo\result\MongoInsertOneResult;

/**
 * @var AsyncMongoDB $mongo
 */

$mongo->insertMany(
    "databaseName",
    "collectionName",
    $documents,
    $options
)->schedule(
    function(MongoInsertManyResult $result): void{
        echo "Inserted {$result->getInsertedCount()}!" . PHP_EOL;
        var_dump($result);
    }
    function(Throwable $e): void{
        echo "Error occurred: {$e->getMessage()}" . PHP_EOL;
    }
);
```

### Find

```php
use Echore\AsyncMongo\AsyncMongoDB;
use Echore\AsyncMongo\result\MongoCursorResult;use Echore\AsyncMongo\result\MongoInsertOneResult;

/**
 * @var AsyncMongoDB $mongo
 */

$mongo->find(
    "databaseName",
    "collectionName",
    $filter,
    $options
)->schedule(
    function(MongoCursorResult $result): void{
        $count = count($result->getArray());
        echo "Matched documents: {$count}!" . PHP_EOL;
        var_dump($result);
    }
    function(Throwable $e): void{
        echo "Error occurred: {$e->getMessage()}" . PHP_EOL;
    }
);
```

### FindOne

```php
use Echore\AsyncMongo\AsyncMongoDB;
use Echore\AsyncMongo\result\MongoCursorResult;use Echore\AsyncMongo\result\MongoDocumentResult;use Echore\AsyncMongo\result\MongoInsertOneResult;

/**
 * @var AsyncMongoDB $mongo
 */

$mongo->findOne(
    "databaseName",
    "collectionName",
    $filter,
    $options
)->schedule(
    function(MongoDocumentResult $result): void{
        $count = $result->getDocument() !== null ? 1 : 0;
        echo "Matched document: {$count}!" . PHP_EOL;
        var_dump($result);
    }
    function(Throwable $e): void{
        echo "Error occurred: {$e->getMessage()}" . PHP_EOL;
    }
);
```

## Advanced Usage

### Transaction

User `buzz` to User `foo` for 10 currency

```php
use Echore\AsyncMongo\AsyncMongoDB;use Echore\AsyncMongo\session\SessionMediator;

/**
 * @var AsyncMongoDB $mongo
 */
 
$collection = $mongo->collection(
    "databaseName",
    "collectionName"
);

$mongo->transaction(
    function(array $successResults, array $errorResults, SessionMediator $session): void{
        if (count($errorResults) === 0){
            echo "No error occurred, committing" . PHP_EOL;
            $session->commit();
        } else {
            echo "Error occurred while transaction, aborting" . PHP_EOL;
            $session->abort();
        }
    },
    function(SessionMediator $session): void{
        echo "Transaction Completed" . PHP_EOL;
    },
    $collection->updateOne(
        ["user" => "foo"],
        [
            '$inc' => [
                "currency" => 10
            ]
        ]
    ),
    $collection->updateOne(
        ["user" => "buzz"],
        [
            '$inc' => [
                "currency" => -10
            ]
        ]
    )
);
```
