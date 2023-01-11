<?php declare(strict_types = 1);

namespace App\Services\Search;

use App\Services\Search\Builders\Builder as SearchBuilder;
use App\Services\Search\Contracts\ScopeWithMetadata;
use App\Services\Search\Eloquent\Searchable;
use App\Services\Search\Eloquent\SearchableImpl;
use App\Services\Search\Properties\Properties;
use App\Services\Search\Properties\Property;
use App\Services\Search\Properties\Relation;
use App\Services\Search\Properties\Text;
use App\Services\Search\Properties\Uuid;
use App\Services\Search\Properties\Value;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use LogicException;
use Mockery;
use Tests\TestCase;

use function sprintf;

/**
 * @internal
 * @covers \App\Services\Search\Configuration
 */
class ConfigurationTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    public function testGetRelations(): void {
        // Prepare
        $model = $this->getModel(
            [
                'meta' => new Text('meta.data'),
            ],
            [
                'sku'        => new Text('sku'),
                'oem'        => new Relation('oem', [
                    'id' => new Text('id'),
                ]),
                'relation'   => new Relation('relation', [
                    'nested' => new Relation('relation-nested', [
                        'property' => new Text('relation-nested-property'),
                    ]),
                ]),
                'properties' => new Properties([
                    'nested'     => new Relation('properties-nested', [
                        'property'   => new Text('properties-nested-property'),
                        'properties' => new Properties([
                            'nested' => new Relation('properties-nested-properties-nested', [
                                'property' => new Text('properties-nested-properties-nested-property'),
                            ]),
                        ]),
                    ]),
                    'properties' => new Properties([
                        'nested' => new Relation('properties-properties-nested', [
                            'property' => new Text('properties-properties-nested-property'),
                        ]),
                    ]),
                ]),
            ],
        );

        // Scope
        $model->addGlobalScope($this->getScope([
            'sku' => new Text('abc.sku'),
            'id'  => new Text('oem.id'),
        ]));

        // Test
        self::assertEquals(
            [
                'meta',
                'abc',
                'oem',
                'relation.relation-nested',
                'properties-nested',
                'properties-nested.properties-nested-properties-nested',
                'properties-properties-nested',
            ],
            $model->getSearchConfiguration()->getRelations(),
        );
    }

    public function testGetProperties(): void {
        // Prepare
        $model = $this->getModel(
            [
                'meta' => new Text('meta.data'),
            ],
            [
                'sku'        => new Text('sku'),
                'oem'        => new Relation('oem', [
                    'id' => new Uuid('id'),
                ]),
                'relation'   => new Relation('a', [
                    'nested' => new Relation('b', [
                        'property' => new Text('c'),
                    ]),
                ]),
                'properties' => new Properties([
                    'nested' => new Relation('a', [
                        'property' => new Text('b'),
                    ]),
                ]),
            ],
        );

        // Scope
        $model->addGlobalScope($this->getScope([
            'sku' => new Text('abc.sku'),
            'id'  => new Uuid('oem.id'),
        ]));

        // Test
        self::assertEquals([
            Configuration::getId()           => new Uuid($model->getKeyName(), false),
            Configuration::getMetadataName() => [
                'sku'  => new Text('abc.sku'),
                'id'   => new Uuid('oem.id'),
                'meta' => new Text('meta.data'),
            ],
            Configuration::getPropertyName() => [
                'sku'        => new Text('sku'),
                'oem'        => new Relation('oem', [
                    'id' => new Uuid('id'),
                ]),
                'relation'   => new Relation('a', [
                    'nested' => new Relation('b', [
                        'property' => new Text('c'),
                    ]),
                ]),
                'properties' => new Properties([
                    'nested' => new Relation('a', [
                        'property' => new Text('b'),
                    ]),
                ]),
            ],
        ], $model->getSearchConfiguration()->getProperties());
    }

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
        self::expectExceptionObject(new LogicException(sprintf(
            'The `%s` trying to redefine `%s` in metadata.',
            $scope::class,
            'meta',
        )));

        $model->getSearchConfiguration();
    }

    /**
     * @dataProvider dataProviderGetSearchable
     *
     * @param array<mixed>            $expected
     * @param array<string, Property> $metadata
     * @param array<string, Property> $properties
     */
    public function testGetSearchable(array $expected, array $metadata, array $properties): void {
        $configuration = new Configuration($this->getModel(), $metadata, $properties);
        $actual        = $configuration->getSearchable();

        self::assertEquals($expected, $actual);
    }

    public function testGetProperty(): void {
        $model         = $this->getModel(
            [
                'meta' => new Text('meta'),
            ],
            [
                'a' => new Text('a'),
                'b' => new Relation('b', [
                    'c' => new Text('c'),
                    'd' => new Relation('d', [
                        'e' => new Text('e'),
                    ]),
                ]),
                'c' => new Properties([
                    'c' => new Text('c'),
                    'd' => new Relation('d', [
                        'e' => new Text('e'),
                    ]),
                ]),
            ],
        );
        $configuration = $model->getSearchConfiguration();

        self::assertEquals('a', $configuration->getProperty(Configuration::getPropertyName('a'))?->getName());
        self::assertEquals('a', $configuration->getProperty(Configuration::getPropertyName('a'))?->getName());
        self::assertEquals('c', $configuration->getProperty(Configuration::getPropertyName('b.c'))?->getName());
        self::assertEquals('e', $configuration->getProperty(Configuration::getPropertyName('b.d.e'))?->getName());
        self::assertEquals('c', $configuration->getProperty(Configuration::getPropertyName('c.c'))?->getName());
        self::assertNull($configuration->getProperty(Configuration::getPropertyName('c.d'))?->getName());
        self::assertEquals('e', $configuration->getProperty(Configuration::getPropertyName('c.d.e'))?->getName());
        self::assertEquals('meta', $configuration->getProperty(Configuration::getMetadataName('meta'))?->getName());
        self::assertNull($configuration->getProperty('meta'));
        self::assertNull($configuration->getProperty('a'));
        self::assertNull($configuration->getProperty('b'));
        self::assertNull($configuration->getProperty('b.d'));
    }

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

        self::assertStringStartsWith('test@', $a->getIndexName());
        self::assertEquals($a->getIndexName(), $b->getIndexName());
        self::assertNotEquals($a->getIndexName(), $c->getIndexName());
    }

    public function testGetIndexAlias(): void {
        $a = new Configuration($this->getModel()->setSearchableAs('should be ignored'), [], []);
        $b = new Configuration($this->getModel(), [], []);

        self::assertEquals('test', $a->getIndexAlias());
        self::assertEquals('test', $b->getIndexAlias());
    }

    public function testGetMappings(): void {
        $actual   = $this
            ->getModel(
                [
                    'meta' => new class('meta') extends Value {
                        public function getType(): string {
                            return 'text';
                        }

                        public function hasKeyword(): bool {
                            return true;
                        }
                    },
                ],
                [
                    'name'       => new class('name') extends Value {
                        public function getType(): string {
                            return 'text';
                        }
                    },
                    'child'      => new Relation('child', [
                        'id'     => new class('id') extends Value {
                            public function getType(): string {
                                return 'keyword';
                            }
                        },
                        'name'   => new class('name') extends Value {
                            public function getType(): string {
                                return 'text';
                            }
                        },
                        'nested' => new Relation('nested', [
                            'name' => new class('name') extends Value {
                                public function getType(): string {
                                    return 'text';
                                }

                                public function hasKeyword(): bool {
                                    return true;
                                }
                            },
                        ]),
                    ]),
                    'properties' => new Properties([
                        'id'     => new class('id') extends Value {
                            public function getType(): string {
                                return 'keyword';
                            }
                        },
                        'nested' => new Relation('nested', [
                            'name' => new class('name') extends Value {
                                public function getType(): string {
                                    return 'text';
                                }
                            },
                        ]),
                    ]),
                ],
            )
            ->getSearchConfiguration()
            ->getMappings();
        $expected = [
            'dynamic'    => 'strict',
            'properties' => [
                Configuration::getId()           => [
                    'type' => 'keyword',
                ],
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
                        'name'       => [
                            'type' => 'text',
                        ],
                        'child'      => [
                            'properties' => [
                                'id'     => [
                                    'type' => 'keyword',
                                ],
                                'name'   => [
                                    'type' => 'text',
                                ],
                                'nested' => [
                                    'properties' => [
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
                        'properties' => [
                            'properties' => [
                                'id'     => [
                                    'type' => 'keyword',
                                ],
                                'nested' => [
                                    'properties' => [
                                        'name' => [
                                            'type' => 'text',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        self::assertEquals($expected, $actual);
    }

    public function testIsIndex(): void {
        $config = Mockery::mock(Configuration::class);
        $config->makePartial();
        $config
            ->shouldReceive('getIndexAlias')
            ->andReturn('test')
            ->atLeast()
            ->once();

        self::assertTrue($config->isIndex('test@12345'));
        self::assertTrue($config->isIndex('test@'));
        self::assertFalse($config->isIndex('test'));
        self::assertFalse($config->isIndex('abc'));
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    /**
     * @param array<string,Property> $metadata
     * @param array<string,Property> $properties
     *
     * @return Model&Searchable
     */
    protected function getModel(array $metadata = [], array $properties = []): Model {
        $model = new class() extends Model implements Searchable {
            use SearchableImpl;

            /**
             * @var array<string,Property>
             */
            public static array $searchMetadata;

            /**
             * @var array<string,Property>
             */
            public static array $searchProperties;

            /**
             * @inheritDoc
             */
            public static function getSearchMetadata(): array {
                return self::$searchMetadata;
            }

            /**
             * @inheritDoc
             */
            public static function getSearchProperties(): array {
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
     * @param array<string,Property> $metadata
     *
     * @return Scope&ScopeWithMetadata<Model>
     */
    protected function getScope(array $metadata): Scope {
        $scope = new class() implements Scope, ScopeWithMetadata {
            /**
             * @var array<string,Property>
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
                    // empty
                ],
                [
                    'm' => new Text('m'),
                ],
                [
                    'a' => new Text('a'),
                    'b' => new Relation('b', [
                        'a' => new Text('a'),
                        'b' => new Text('a'),
                    ]),
                ],
            ],
            'no searchable + metadata searchable' => [
                [
                    Configuration::getMetadataName('m'),
                ],
                [
                    'm' => new Text('m', true),
                ],
                [
                    'a' => new Text('a'),
                    'b' => new Relation('b', [
                        'a' => new Text('a'),
                        'b' => new Text('a'),
                    ]),
                ],
            ],
            'all searchable'                      => [
                [
                    Configuration::getMetadataName('m'),
                    Configuration::getPropertyName('a'),
                    Configuration::getPropertyName('b.a'),
                    Configuration::getPropertyName('b.b'),
                    Configuration::getPropertyName('c.a'),
                    Configuration::getPropertyName('c.b.a'),
                    Configuration::getPropertyName('c.b.b'),
                ],
                [
                    'm' => new Text('m', true),
                ],
                [
                    'a' => new Text('a', true),
                    'b' => new Relation('b', [
                        'a' => new Text('a', true),
                        'b' => new Text('a', true),
                    ]),
                    'c' => new Properties([
                        'a' => new Text('a', true),
                        'b' => new Relation('b', [
                            'a' => new Text('a', true),
                            'b' => new Text('a', true),
                        ]),
                    ]),
                ],
            ],
            'all searchable + no metadata'        => [
                [
                    Configuration::getPropertyName('a'),
                ],
                [
                    // empty
                ],
                [
                    'a' => new Uuid('a', true),
                ],
            ],
            'mixed one'                           => [
                [
                    Configuration::getMetadataName('m'),
                    Configuration::getPropertyName('b.b'),
                ],
                [
                    'm' => new Text('m', true),
                ],
                [
                    'a' => new Text('a'),
                    'b' => new Relation('b', [
                        'a' => new Text('a'),
                        'b' => new Text('a', true),
                    ]),
                ],
            ],
            'mixed two'                           => [
                [
                    Configuration::getPropertyName('b.a'),
                    Configuration::getPropertyName('b.b'),
                ],
                [
                    // empty
                ],
                [
                    'a' => new Text('a'),
                    'b' => new Relation('b', [
                        'a' => new Text('a', true),
                        'b' => new Text('a', true),
                    ]),
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
                    'a' => new Uuid('a', true),
                    'b' => new Relation('b', [
                        'a' => new Text('a'),
                        'b' => new Text('a'),
                    ]),
                ],
            ],
            'mixed four'                          => [
                [
                    Configuration::getPropertyName('a'),
                    Configuration::getPropertyName('b.a'),
                    Configuration::getPropertyName('b.c.b'),
                    Configuration::getPropertyName('c.b.a'),
                ],
                [
                    // empty
                ],
                [
                    'a' => new Uuid('a', true),
                    'b' => new Relation('b', [
                        'a' => new Uuid('a', true),
                        'b' => new Text('a'),
                        'c' => new Relation('c', [
                            'a' => new Text('a'),
                            'b' => new Text('b', true),
                        ]),
                    ]),
                    'c' => new Properties([
                        'a' => new Text('a', false),
                        'b' => new Relation('b', [
                            'a' => new Text('a', true),
                            'b' => new Text('a', false),
                        ]),
                    ]),
                ],
            ],
        ];
    }
    // </editor-fold>
}
