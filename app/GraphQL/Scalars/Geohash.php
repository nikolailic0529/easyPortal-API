<?php declare(strict_types = 1);

namespace App\GraphQL\Scalars;

use Exception;
use GraphQL\Error\Error;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;
use InvalidArgumentException;
use League\Geotools\Geohash\Geohash as GeotoolsGeohash;
use League\Geotools\Geotools;

use function is_string;
use function mb_strlen;
use function sprintf;

/**
 * Geohash string
 *
 * https://en.wikipedia.org/wiki/Geohash
 */
class Geohash extends ScalarType {
    public function serialize(mixed $value): string {
        try {
            return $this->parseValue($value)->getGeohash();
        } catch (Error $exception) {
            throw $exception;
        } catch (Exception $exception) {
            throw new Error(
                Utils::printSafeJson($exception->getMessage()),
            );
        }
    }

    public function parseValue(mixed $value): GeotoolsGeohash {
        try {
            if ($value instanceof GeotoolsGeohash) {
                if ($value->getGeohash() === null) {
                    $tools = new Geotools();
                    $value = $tools->geohash()->encode($value->getCoordinate());
                }
            } elseif (is_string($value)) {
                $length = mb_strlen($value);
                $tools  = new Geotools();
                $value  = $tools->geohash()->decode($value);
                $value  = $tools->geohash()->encode($value->getCoordinate(), $length);
            } else {
                throw new InvalidArgumentException('This is not a geohash.');
            }
        } catch (Exception $exception) {
            throw new Error(
                Utils::printSafeJson($exception->getMessage()),
            );
        }

        return $value;
    }

    public function parseLiteral(Node $valueNode, ?array $variables = null): GeotoolsGeohash {
        if (!($valueNode instanceof StringValueNode)) {
            throw new Error(sprintf(
                'Query error: Can only parse strings, `%s` given',
                $valueNode->kind,
            ), $valueNode);
        }

        return $this->parseValue($valueNode->value);
    }
}
