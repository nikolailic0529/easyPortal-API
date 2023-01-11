<?php declare(strict_types = 1);

namespace App\GraphQL\Scalars;

use Exception;
use GraphQL\Error\Error;
use GraphQL\Language\AST\BooleanValueNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use League\Geotools\Geohash\Geohash as GeotoolsGeohash;
use Tests\TestCase;

use function sprintf;

/**
 * @internal
 * @covers \App\GraphQL\Scalars\Geohash
 */
class GeohashTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderSerialize
     */
    public function testSerialize(Exception|string $expected, mixed $value): void {
        if ($expected instanceof Exception) {
            self::expectExceptionObject($expected);
        }

        $scalar = new Geohash();
        $actual = $scalar->serialize($value);

        self::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider dataProviderParseValue
     */
    public function testParseValue(Exception|GeotoolsGeohash $expected, mixed $value): void {
        if ($expected instanceof Exception) {
            self::expectExceptionObject($expected);
        }

        $scalar = new Geohash();
        $actual = $scalar->parseValue($value);

        self::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider dataProvideParseLiteral
     */
    public function testParseLiteral(Exception|GeotoolsGeohash $expected, Node $node): void {
        if ($expected instanceof Exception) {
            self::expectExceptionObject($expected);
        }

        $scalar = new Geohash();
        $actual = $scalar->parseLiteral($node);

        self::assertEquals($expected, $actual);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,array{Exception|string,mixed}>
     */
    public function dataProviderSerialize(): array {
        return [
            'valid geohash'           => [
                'spey',
                (new GeotoolsGeohash())->encode((new GeotoolsGeohash())->decode('spey61y')->getCoordinate(), 4),
            ],
            'valid geohash (decoded)' => [
                'spey61ys0000',
                (new GeotoolsGeohash())->decode('spey61y'),
            ],
            'valid geohash (string)'  => [
                'spey',
                'spey',
            ],
            'invalid geohash'         => [
                new Error('This geo hash is invalid.'),
                '-1',
            ],
            'not a geohash'           => [
                new Error('This is not a geohash.'),
                123,
            ],
        ];
    }

    /**
     * @return array<string,array{Exception|GeotoolsGeohash,mixed}>
     */
    public function dataProviderParseValue(): array {
        return [
            'valid geohash'           => [
                (new GeotoolsGeohash())->encode((new GeotoolsGeohash())->decode('spey61y')->getCoordinate(), 4),
                (new GeotoolsGeohash())->encode((new GeotoolsGeohash())->decode('spey61y')->getCoordinate(), 4),
            ],
            'valid geohash (decoded)' => [
                (new GeotoolsGeohash())->encode((new GeotoolsGeohash())->decode('spey61ys0000')->getCoordinate()),
                (new GeotoolsGeohash())->decode('spey61y'),
            ],
            'valid geohash (string)'  => [
                (new GeotoolsGeohash())->encode((new GeotoolsGeohash())->decode('spey61ys0000')->getCoordinate(), 4),
                'spey',
            ],
            'invalid geohash'         => [
                new Error('This geo hash is invalid.'),
                '-1',
            ],
            'not a geohash'           => [
                new Error('This is not a geohash.'),
                123,
            ],
        ];
    }

    /**
     * @return array<string,array{Exception|GeotoolsGeohash,Node}>
     */
    public function dataProvideParseLiteral(): array {
        return [
            StringValueNode::class  => [
                (new GeotoolsGeohash())->encode((new GeotoolsGeohash())->decode('spey61y')->getCoordinate(), 7),
                new StringValueNode(['value' => 'spey61y']),
            ],
            BooleanValueNode::class => [
                new Error(sprintf(
                    'Query error: Can only parse strings, `%s` given',
                    'abc',
                )),
                new BooleanValueNode(['value' => true, 'kind' => 'abc']),
            ],
        ];
    }
    // </editor-fold>
}
