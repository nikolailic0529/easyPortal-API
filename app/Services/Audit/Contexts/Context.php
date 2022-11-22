<?php declare(strict_types = 1);

namespace App\Services\Audit\Contexts;

use JsonSerializable;
use ReflectionObject;

// todo(PHP 8.2): Since PHP 8.1 it is possible to use array unpacking + named
//      args => we can update JsonObject and use it as a parent class for this
//      - https://github.com/fakharanwar/easyPortal-API/issues/1123
//      - https://wiki.php.net/rfc/array_unpacking_string_keys

class Context implements JsonSerializable {
    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array {
        $properties = (new ReflectionObject($this))->getProperties();
        $json       = [];

        foreach ($properties as $property) {
            if (!$property->isPrivate() && $property->isInitialized($this)) {
                $json[$property->getName()] = $property->getValue($this);
            }
        }

        return $json;
    }
}
