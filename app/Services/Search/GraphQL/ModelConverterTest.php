<?php declare(strict_types = 1);

namespace App\Services\Search\GraphQL;

use App\Services\Search\Eloquent\Searchable;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils\SchemaPrinter;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

use function array_map;
use function implode;

/**
 * @internal
 * @coversDefaultClass \App\Services\Search\GraphQL\ModelConverter
 */
class ModelConverterTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::toInputObjectTypes
     */
    public function testToInputObjectTypes(): void {
        $converter = $this->app->make(ModelConverter::class);
        $actual    = $this->getGraphQL($converter->toInputObjectTypes(ModelConverterTest_Model::class));
        $expected  = $this->getTestData()->content('.graphql');

        $this->assertEquals($expected, $actual);
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    /**
     * @param array<\GraphQL\Type\Definition\Type> $types
     */
    protected function getGraphQL(array $types): string {
        $schema = array_map(static function (Type $type): string {
            return SchemaPrinter::printType($type);
        }, $types);
        $schema = implode("\n", $schema)."\n";

        return $schema;
    }
    //</editor-fold>
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class ModelConverterTest_Model extends Model {
    use Searchable;

    /**
     * @inheritDoc
     */
    public static function getSearchProperties(): array {
        return [
            'name'  => '1',
            'test'  => 'value',
            'child' => [
                'name'    => '2',
                'another' => 'value',
                'nested'  => [
                    'name' => 'name',
                ],
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public static function getSearchSearchable(): array {
        return ['*'];
    }
}

// @phpcs:enable
