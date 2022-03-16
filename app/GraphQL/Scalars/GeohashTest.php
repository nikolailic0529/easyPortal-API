<?php declare(strict_types = 1);

namespace App\GraphQL\Scalars;

use Exception;
use GraphQL\Error\Error;
use GraphQL\Language\AST\BooleanValueNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use League\Geotools\Geohash\Geohash as GeotoolsGeohash;
use League\Geotools\Geotools;
use Tests\TestCase;

use function sprintf;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Scalars\Geohash
 */
class GeohashTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::serialize
     * @dataProvider dataProviderSerialize
     */
    public function testSerialize(Exception|string $expected, mixed $value): void {
        if ($expected instanceof Exception) {
            $this->expectExceptionObject($expected);
        }

        $scalar = new Geohash();
        $actual = $scalar->serialize($value);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::parseValue
     * @dataProvider dataProviderParseValue
     */
    public function testParseValue(Exception|GeotoolsGeohash $expected, mixed $value): void {
        if ($expected instanceof Exception) {
            $this->expectExceptionObject($expected);
        }

        $scalar = new Geohash();
        $actual = $scalar->parseValue($value);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::parseLiteral
     * @dataProvider dataProvideParseLiteral
     */
    public function testParseLiteral(Exception|GeotoolsGeohash $expected, Node $node): void {
        if ($expected instanceof Exception) {
            $this->expectExceptionObject($expected);
        }

        $scalar = new Geohash();
        $actual = $scalar->parseLiteral($node);

        $this->assertEquals($expected, $actual);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,array{Exception|string,mixed}>
     */
    public function dataProviderSerialize(): array {
        $tools = new Geotools();

        return [
            'valid geohash'           => [
                'spey',
                $tools->geohash()->encode($tools->geohash()->decode('spey61y')->getCoordinate(), 4),
            ],
            'valid geohash (decoded)' => [
                'spey61ys0000',
                $tools->geohash()->decode('spey61y'),
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
        $tools = new Geotools();

        return [
            'valid geohash'           => [
                $tools->geohash()->encode($tools->geohash()->decode('spey61y')->getCoordinate(), 4),
                $tools->geohash()->encode($tools->geohash()->decode('spey61y')->getCoordinate(), 4),
            ],
            'valid geohash (decoded)' => [
                $tools->geohash()->encode($tools->geohash()->decode('spey61ys0000')->getCoordinate()),
                (new Geotools())->geohash()->decode('spey61y'),
            ],
            'valid geohash (string)'  => [
                $tools->geohash()->encode($tools->geohash()->decode('spey61ys0000')->getCoordinate(), 4),
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
        $tools = new Geotools();

        return [
            StringValueNode::class  => [
                $tools->geohash()->encode($tools->geohash()->decode('spey61y')->getCoordinate(), 7),
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
