<?php declare(strict_types = 1);

namespace App\Services\Search\GraphQL;

use App\Services\Search\Eloquent\Searchable;
use App\Services\Search\Eloquent\SearchableImpl;
use App\Services\Search\Properties\Properties;
use App\Services\Search\Properties\Relation;
use App\Services\Search\Properties\Text;
use Closure;
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

        self::assertEquals($expected, $actual);
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    /**
     * @param array<string, Closure(): Type> $types
     */
    protected function getGraphQL(array $types): string {
        $schema = array_map(static function (Closure $type): string {
            return SchemaPrinter::printType($type());
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
class ModelConverterTest_Model extends Model implements Searchable {
    use SearchableImpl;

    /**
     * @inheritDoc
     */
    public static function getSearchProperties(): array {
        return [
            'name'  => new Text('name'),
            'test'  => new Text('value'),
            'child' => new Relation('child', [
                'name'    => new Text('name'),
                'another' => new Text('another'),
                'nested'  => new Relation('nested', [
                    'name' => new Text('name'),
                ]),
            ]),
            'array' => new Properties([
                'name'   => new Text('name'),
                'test'   => new Text('value'),
                'nested' => new Relation('nested', [
                    'name' => new Text('name'),
                ]),
            ]),
        ];
    }
}

// @phpcs:enable
