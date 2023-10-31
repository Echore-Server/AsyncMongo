<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\bson;

use ArrayObject;
use JsonSerializable;
use MongoDB\BSON\Serializable;
use MongoDB\BSON\Unserializable;
use ReturnTypeWillChange;
use function MongoDB\recursive_copy;

class BSONArray extends ArrayObject implements JsonSerializable, Serializable, Unserializable {
	/**
	 * Factory method for var_export().
	 *
	 * @see https://php.net/oop5.magic#object.set-state
	 * @see https://php.net/var-export
	 * @return self
	 */
	public static function __set_state(array $properties) {
		$array = new static();
		$array->exchangeArray($properties);

		return $array;
	}

	/**
	 * Clone this BSONArray.
	 */
	public function __clone() {
		foreach ($this as $key => $value) {
			$this[$key] = recursive_copy($value);
		}
	}

	/**
	 * Serialize the array to BSON.
	 *
	 * The array data will be numerically reindexed to ensure that it is stored
	 * as a BSON array.
	 *
	 * @see https://php.net/mongodb-bson-serializable.bsonserialize
	 * @return array
	 */
	#[ReturnTypeWillChange]
	public function bsonSerialize() {
		return array_values($this->getArrayCopy());
	}

	/**
	 * Unserialize the document to BSON.
	 *
	 * @see https://php.net/mongodb-bson-unserializable.bsonunserialize
	 * @param array $data Array data
	 */
	#[ReturnTypeWillChange]
	public function bsonUnserialize(array $data) {
		self::__construct($data);
	}

	/**
	 * Serialize the array to JSON.
	 *
	 * The array data will be numerically reindexed to ensure that it is stored
	 * as a JSON array.
	 *
	 * @see https://php.net/jsonserializable.jsonserialize
	 * @return array
	 */
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return array_values($this->getArrayCopy());
	}
}
