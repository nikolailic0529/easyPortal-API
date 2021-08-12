<?php declare(strict_types = 1);

namespace App\Services\Search;

use App\Services\Search\Builders\Builder as SearchBuilder;
use App\Services\Search\Eloquent\Searchable;
use App\Services\Search\Properties\Text;
use App\Services\Search\Properties\Uuid;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use LogicException;
use Mockery;
use Tests\TestCase;

use function sprintf;

/**
 * @internal
 * @coversDefaultClass \App\Services\Search\Configuration
 */
class ConfigurationTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::getRelations
     */
    public function testGetRelations(): void {
        // Prepare
        $model = $this->getModel(
            [
                'meta' => new Text('meta.data'),
            ],
            [
                'sku' => new Text('sku'),
                'oem' => [
                    'id' => new Text('oem.id'),
                ],
            ],
        );

        // Scope
        $model->addGlobalScope($this->getScope([
            'sku' => new Text('abc.sku'),
            'id'  => new Text('oem.id'),
        ]));

        // Test
        $this->assertEquals(
            ['meta', 'abc', 'oem'],
            $model->getSearchConfiguration()->getRelations(),
        );
    }

    /**
     * @covers ::__construct
     * @covers ::getProperties
     * @covers ::buildProperties
     */
    public function testGetProperties(): void {
        // Prepare
        $model = $this->getModel(
            [
                'meta' => new Text('meta.data'),
            ],
            [
                'sku' => new Text('sku'),
                'oem' => [
                    'id' => new Uuid('oem.id'),
                ],
            ],
        );

        // Scope
        $model->addGlobalScope($this->getScope([
            'sku' => new Text('abc.sku'),
            'id'  => new Uuid('oem.id'),
        ]));

        // Test
        $this->assertEquals([
            Configuration::getMetadataName() => [
                'sku'  => new Text('abc.sku'),
                'id'   => new Uuid('oem.id'),
                'meta' => new Text('meta.data'),
            ],
            Configuration::getPropertyName() => [
                'sku' => new Text('sku'),
                'oem' => [
                    'id' => new Uuid('oem.id'),
                ],
            ],
        ], $model->getSearchConfiguration()->getProperties());
    }

    /**
     * @covers ::__construct
     * @covers ::getProperties
     * @covers ::buildProperties
     */
    public function testGetPropertiesMetadataConflict(): void {
        // Prepare
        $model = $this->getModel([
            'meta' => new Text('meta.data'),
        ]);

        // Scope
        $scope = $this->getScope([
            'meta' => new Text('meta.data'),
        ]);

        $model->addGlobalScope($scope);

        // Test
        $this->expectExceptionObject(new LogicException(sprintf(
            'The `%s` trying to redefine `%s` in metadata.',
            $scope::class,
            'meta',
        )));

        $model->getSearchConfiguration();
    }

    /**
     * @dataProvider dataProviderGetSearchable
     *
     * @covers ::getSearchable
     * @covers ::getSearchableProcess
     *
     * @param array<mixed> $expected
     * @param array<mixed> $metadata
     * @param array<mixed> $properties
     */
    public function testGetSearchable(array $expected, array $metadata, array $properties): void {
        $configuration = new Configuration($this->getModel(), $metadata, $properties);
        $actual        = $configuration->getSearchable();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::getProperty
     */
    public function testGetSearchProperty(): void {
        $model         = $this->getModel(
            [
                'meta' => new Text('meta'),
            ],
            [
                'a' => new Text('a'),
                'b' => [
                    'c' => new Text('c'),
                ],
            ],
        );
        $configuration = $model->getSearchConfiguration();

        $this->assertEquals('a', $configuration->getProperty(Configuration::getPropertyName('a'))?->getName());
        $this->assertEquals('a', $configuration->getProperty(Configuration::getPropertyName('a'))?->getName());
        $this->assertEquals('c', $configuration->getProperty(Configuration::getPropertyName('b.c'))?->getName());
        $this->assertEquals('meta', $configuration->getProperty(Configuration::getMetadataName('meta'))?->getName());
        $this->assertNull($configuration->getProperty('meta'));
        $this->assertNull($configuration->getProperty('a'));
    }

    /**
     * @covers ::getIndexName
     */
    public function testGetIndexName(): void {
        $a = new Configuration(
            $this->getModel()->setSearchableAs('should be ignored'),
            [
                'meta' => new Text('meta'),
            ],
            [
                'name' => new Text('name'),
            ],
        );
        $b = new Configuration(
            $this->getModel()->setSearchableAs('should be ignored'),
            [
                'meta' => new Text('meta'),
            ],
            [
                'name' => new Text('name'),
            ],
        );
        $c = new Configuration(
            $this->getModel()->setSearchableAs('should be ignored'),
            [
                'meta' => new Uuid('meta'),
            ],
            [
                'name' => new Text('name'),
            ],
        );

        $this->assertStringStartsWith('test@', $a->getIndexName());
        $this->assertEquals($a->getIndexName(), $b->getIndexName());
        $this->assertNotEquals($a->getIndexName(), $c->getIndexName());
    }

    /**
     * @covers ::getIndexAlias
     */
    public function testGetIndexAlias(): void {
        $a = new Configuration($this->getModel()->setSearchableAs('should be ignored'), [], []);
        $b = new Configuration($this->getModel(), [], []);

        $this->assertEquals('test', $a->getIndexAlias());
        $this->assertEquals('test', $b->getIndexAlias());
    }

    /**
     * @covers ::getMappings
     * @covers ::getMappingsProcess
     */
    public function testGetMappings(): void {
        $actual   = $this
            ->getModel(
                [
                    'meta' => new Text('meta'),
                ],
                [
                    'name'  => new Text('name'),
                    'child' => [
                        'id'   => new Uuid('id'),
                        'name' => new Text('name'),
                    ],
                ],
            )
            ->getSearchConfiguration()
            ->getMappings();
        $expected = [
            'properties' => [
                Configuration::getMetadataName() => [
                    'properties' => [
                        'meta' => [
                            'type'   => 'text',
                            'fields' => [
                                'keyword' => [
                                    'type' => 'keyword',
                                ],
                            ],
                        ],
                    ],
                ],
                Configuration::getPropertyName() => [
                    'properties' => [
                        'name'  => [
                            'type'   => 'text',
                            'fields' => [
                                'keyword' => [
                                    'type' => 'keyword',
                                ],
                            ],
                        ],
                        'child' => [
                            'properties' => [
                                'id'   => [
                                    'type' => 'keyword',
                                ],
                                'name' => [
                                    'type'   => 'text',
                                    'fields' => [
                                        'keyword' => [
                                            'type' => 'keyword',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::isIndex
     */
    public function testIsIndex(): void {
        $config = Mockery::mock(Configuration::class);
        $config->makePartial();
        $config
            ->shouldReceive('getIndexAlias')
            ->andReturn('test')
            ->atLeast()
            ->once();

        $this->assertTrue($config->isIndex('test@12345'));
        $this->assertTrue($config->isIndex('test@'));
        $this->assertFalse($config->isIndex('test'));
        $this->assertFalse($config->isIndex('abc'));
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    /**
     * @param array<mixed> $metadata
     * @param array<mixed> $properties
     *
     * @return \Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable
     */
    protected function getModel(array $metadata = [], array $properties = []): Model {
        $model = new class() extends Model {
            use Searchable;

            /**
             * @var array<mixed>
             */
            public static array $searchMetadata;

            /**
             * @var array<mixed>
             */
            public static array $searchProperties;

            /**
             * @inheritDoc
             */
            protected static function getSearchMetadata(): array {
                return self::$searchMetadata;
            }

            /**
             * @inheritDoc
             */
            protected static function getSearchProperties(): array {
                return self::$searchProperties;
            }

            public function scoutSearchableAs(): string {
                return 'test';
            }
        };

        $model::$searchMetadata   = $metadata;
        $model::$searchProperties = $properties;

        return $model;
    }

    /**
     * @param array<mixed> $metadata
     *
     * @return \Illuminate\Database\Eloquent\Scope&\App\Services\Search\ScopeWithMetadata
     */
    protected function getScope(array $metadata): Scope {
        $scope = new class() implements Scope, ScopeWithMetadata {
            /**
             * @var array<mixed>
             */
            public static array $searchMetadata;

            public function apply(EloquentBuilder $builder, Model $model): void {
                // empty
            }

            public function applyForSearch(SearchBuilder $builder, Model $model): void {
                // empty
            }

            /**
             * @inheritDoc
             */
            public function getSearchMetadata(Model $model): array {
                return self::$searchMetadata;
            }
        };

        $scope::$searchMetadata = $metadata;

        return $scope;
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, mixed>
     */
    public function dataProviderGetSearchable(): array {
        return [
            'no searchable'                       => [
                [
                    '',
                ],
                [
                    'm' => new Text('m'),
                ],
                [
                    'a' => new Text('a'),
                    'b' => [
                        'a' => new Text('a'),
                        'b' => new Text('a'),
                    ],
                ],
            ],
            'no searchable + metadata searchable' => [
                [
                    Configuration::getMetadataName('*'),
                ],
                [
                    'm' => new Text('m', true),
                ],
                [
                    'a' => new Text('a'),
                    'b' => [
                        'a' => new Text('a'),
                        'b' => new Text('a'),
                    ],
                ],
            ],
            'all searchable'                      => [
                [
                    '*',
                ],
                [
                    'm' => new Text('m', true),
                ],
                [
                    'a' => new Text('a', true),
                    'b' => [
                        'a' => new Text('a', true),
                        'b' => new Text('a', true),
                    ],
                ],
            ],
            'mixed one'                           => [
                [
                    Configuration::getMetadataName('*'),
                    Configuration::getPropertyName('b.b'),
                ],
                [
                    'm' => new Text('m', true),
                ],
                [
                    'a' => new Text('a'),
                    'b' => [
                        'a' => new Text('a'),
                        'b' => new Text('a', true),
                    ],
                ],
            ],
            'mixed two'                           => [
                [
                    Configuration::getPropertyName('b.*'),
                ],
                [
                    // empty
                ],
                [
                    'a' => new Text('a'),
                    'b' => [
                        'a' => new Text('a', true),
                        'b' => new Text('a', true),
                    ],
                ],
            ],
            'mixed three'                         => [
                [
                    Configuration::getPropertyName('a'),
                ],
                [
                    // empty
                ],
                [
                    'a' => new Text('a', true),
                    'b' => [
                        'a' => new Text('a'),
                        'b' => new Text('a'),
                    ],
                ],
            ],
            'mixed four'                          => [
                [
                    Configuration::getPropertyName('a'),
                    Configuration::getPropertyName('b.a'),
                ],
                [
                    // empty
                ],
                [
                    'a' => new Text('a', true),
                    'b' => [
                        'a' => new Text('a', true),
                        'b' => new Text('a'),
                    ],
                ],
            ],
        ];
    }
    // </editor-fold>
}
