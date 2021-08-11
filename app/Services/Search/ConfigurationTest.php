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
        $model = new class() extends Model {
            use Searchable;

            /**
             * @inheritDoc
             */
            protected static function getSearchProperties(): array {
                return [
                    'sku' => new Text('sku'),
                    'oem' => [
                        'id' => new Text('oem.id'),
                    ],
                ];
            }

            /**
             * @inheritDoc
             */
            protected static function getSearchMetadata(): array {
                return [
                    'meta' => new Text('meta.data'),
                ];
            }
        };

        // Scope
        $model->addGlobalScope(new class() implements Scope, ScopeWithMetadata {
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
                return [
                    'sku' => new Text('abc.sku'),
                    'id'  => new Text('oem.id'),
                ];
            }
        });

        // Test
        $this->assertEquals(
            ['meta', 'abc', 'oem'],
            $model->getSearchableConfiguration()->getRelations(),
        );
    }

    /**
     * @covers ::__construct
     * @covers ::getProperties
     * @covers ::buildProperties
     */
    public function testGetProperties(): void {
        // Prepare
        $model = new class() extends Model {
            use Searchable;

            /**
             * @inheritDoc
             */
            protected static function getSearchProperties(): array {
                return [
                    'sku' => new Text('sku'),
                    'oem' => [
                        'id' => new Uuid('oem.id'),
                    ],
                ];
            }

            /**
             * @inheritDoc
             */
            protected static function getSearchMetadata(): array {
                return [
                    'meta' => new Text('meta.data'),
                ];
            }
        };

        // Scope
        $model->addGlobalScope(new class() implements Scope, ScopeWithMetadata {
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
                return [
                    'sku' => new Text('abc.sku'),
                    'id'  => new Uuid('oem.id'),
                ];
            }
        });

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
        ], $model->getSearchableConfiguration()->getProperties());
    }

    /**
     * @covers ::__construct
     * @covers ::getProperties
     * @covers ::buildProperties
     */
    public function testGetPropertiesMetadataConflict(): void {
        // Prepare
        $model = new class() extends Model {
            use Searchable;

            /**
             * @inheritDoc
             */
            protected static function getSearchProperties(): array {
                return [];
            }

            /**
             * @inheritDoc
             */
            protected static function getSearchMetadata(): array {
                return [
                    'meta' => new Text('meta.data'),
                ];
            }
        };

        // Scope
        $scope = new class() implements Scope, ScopeWithMetadata {
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
                return [
                    'meta' => new Text('meta.data'),
                ];
            }
        };

        $model->addGlobalScope($scope);

        // Test
        $this->expectExceptionObject(new LogicException(sprintf(
            'The `%s` trying to redefine `%s` in metadata.',
            $scope::class,
            'meta',
        )));

        $model->getSearchableConfiguration();
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
        };

        $model::$searchMetadata   = $metadata;
        $model::$searchProperties = $properties;

        $this->assertEquals($expected, $model->getSearchableConfiguration()->getSearchable());
    }

    /**
     * @covers ::getProperty
     */
    public function testGetSearchProperty(): void {
        $model = new class() extends Model {
            use Searchable;

            /**
             * @inheritDoc
             */
            protected static function getSearchProperties(): array {
                return [
                    'a' => new Text('a'),
                    'b' => [
                        'c' => new Text('c'),
                    ],
                ];
            }

            /**
             * @inheritDoc
             */
            protected static function getSearchMetadata(): array {
                return [
                    'meta' => new Text('meta'),
                ];
            }
        };

        $configuration = $model->getSearchableConfiguration();

        $this->assertEquals('a', $configuration->getProperty(Configuration::getPropertyName('a'))?->getName());
        $this->assertEquals('a', $configuration->getProperty(Configuration::getPropertyName('a'))?->getName());
        $this->assertEquals('c', $configuration->getProperty(Configuration::getPropertyName('b.c'))?->getName());
        $this->assertEquals('meta', $configuration->getProperty(Configuration::getMetadataName('meta'))?->getName());
        $this->assertNull($configuration->getProperty('meta'));
        $this->assertNull($configuration->getProperty('a'));
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
