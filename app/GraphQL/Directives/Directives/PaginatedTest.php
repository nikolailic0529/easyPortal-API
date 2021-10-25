<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives;

use Closure;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\Builders\BuilderDataProvider;
use Tests\TestCase;
use Tests\WithGraphQLSchema;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Directives\Directives\Paginated
 */
class PaginatedTest extends TestCase {
    use WithGraphQLSchema;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::manipulateArgDefinition
     * @covers ::getLimitField
     * @covers ::getOffsetField
     *
     * @dataProvider dataProviderManipulateArgDefinition
     *
     * @param array<string, mixed> $settings
     */
    public function testManipulateArgDefinition(string $expected, array $settings): void {
        $this->setSettings($settings);

        $this->assertGraphQLSchemaEquals(
            $this->getGraphQLSchemaExpected($expected, '~schema.graphql'),
            $this->getTestData()->content('~schema.graphql'),
        );
    }

    /**
     * @covers ::handleBuilder
     *
     * @dataProvider dataProviderHandleBuilder
     *
     * @param array{query: string, bindings: array<mixed>} $expected
     * @param array<mixed>                                 $args
     */
    public function testHandleBuilder(
        array $expected,
        Closure $builder,
        array $args,
    ): void {
        $directive = $this->app->make(Paginated::class);
        $builder   = $builder($this);
        $builder   = $directive->handleBuilder($builder, $args);

        $this->assertDatabaseQueryEquals($expected, $builder);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderManipulateArgDefinition(): array {
        return [
            'default limit is set'     => [
                '~expected-with-limit.graphql',
                [
                    'ep.pagination.limit.default' => 25,
                    'ep.pagination.limit.max'     => 123,
                ],
            ],
            'default limit is not set' => [
                '~expected-without-limit.graphql',
                [
                    'ep.pagination.limit.default' => null,
                    'ep.pagination.limit.max'     => 321,
                ],
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function dataProviderHandleBuilder(): array {
        return (new CompositeDataProvider(
            new BuilderDataProvider(),
            new ArrayDataProvider([
                'limit + offset' => [
                    [
                        'query'    => 'select * from `tmp` limit 123 offset 45',
                        'bindings' => [],
                    ],
                    [
                        'limit'  => 123,
                        'offset' => 45,
                    ],
                ],
                'limit'          => [
                    [
                        'query'    => 'select * from `tmp` limit 123',
                        'bindings' => [],
                    ],
                    [
                        'limit' => 123,
                    ],
                ],
                'none'           => [
                    [
                        'query'    => 'select * from `tmp`',
                        'bindings' => [],
                    ],
                    [
                        // empty
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
