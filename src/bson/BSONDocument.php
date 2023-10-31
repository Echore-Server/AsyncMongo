<?php

declare(strict_types=1);

namespace Echore\AsyncMongo\bson;

use ArrayObject;
use JsonSerializable;
use MongoDB\BSON\Serializable;
use MongoDB\BSON\Unserializable;
use ReturnTypeWillChange;
use function MongoDB\recursive_copy;

class BSONDocument extends ArrayObject implements Serializable, Unserializable, JsonSerializable {
	/**
	 * Factory method for var_export().
	 *
	 * @see https://php.net/oop5.magic#object.set-state
	 * @see https://php.net/var-export
	 * @return self
	 */
	public static function __set_state(array $properties) {
		$document = new static();
		$document->exchangeArray($properties);

		return $document;
	}

	/**
	 * Deep clone this BSONDocument.
	 */
	public function __clone() {
		foreach ($this as $key => $value) {
			$this[$key] = recursive_copy($value);
		}
	}

	/**
	 * Serialize the document to BSON.
	 *
	 * @see https://php.net/mongodb-bson-serializable.bsonserialize
	 * @return object
	 */
	#[ReturnTypeWillChange]
	public function bsonSerialize() {
		return (object) $this->getArrayCopy();
	}

	/**
	 * Unserialize the document to BSON.
	 *
	 * @see https://php.net/mongodb-bson-unserializable.bsonunserialize
	 * @param array $data Array data
	 */
	#[ReturnTypeWillChange]
	public function bsonUnserialize(array $data) {
		parent::__construct($data, ArrayObject::ARRAY_AS_PROPS);
	}

	/**
	 * This overrides the parent constructor to allow property access of entries
	 * by default.
	 *
	 * @see https://php.net/arrayobject.construct
	 */
	public function __construct(array $input = [], int $flags = ArrayObject::ARRAY_AS_PROPS, string $iteratorClass = 'ArrayIterator') {
		parent::__construct($input, $flags, $iteratorClass);
	}

	/**
	 * Serialize the array to JSON.
	 *
	 * @see https://php.net/jsonserializable.jsonserialize
	 * @return object
	 */
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return (object) $this->getArrayCopy();
	}
}
