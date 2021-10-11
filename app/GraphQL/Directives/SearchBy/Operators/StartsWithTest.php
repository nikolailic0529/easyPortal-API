<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\SearchBy\Operators;

use App\GraphQL\Directives\SearchBy\Metadata;
use Closure;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\Builders\BuilderDataProvider;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Directives\SearchBy\Operators\StartsWith
 */
class StartsWithTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::apply
     *
     * @dataProvider dataProviderApply
     *
     * @param array{sql: string, bindings: array<mixed>} $expected
     */
    public function testApply(
        array $expected,
        Closure $builder,
        string $property,
        bool $fulltext,
        mixed $value,
    ): void {
        $this->override(Metadata::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('isFulltextIndexExists')
                ->never();
        });

        $operator = $this->app->make(StartsWith::class);
        $builder  = $builder($this);
        $builder  = $operator->apply($builder, $property, $value);
        $actual   = [
            'sql'      => $builder->toSql(),
            'bindings' => $builder->getBindings(),
        ];

        $this->assertEquals($expected, $actual);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderApply(): array {
        return (new CompositeDataProvider(
            new BuilderDataProvider(),
            new ArrayDataProvider([
                'with fulltext'    => [
                    [
                        'sql'      => 'select * from `tmp` where (`property` like ?)',
                        'bindings' => [
                            'a\\\\%b%',
                        ],
                    ],
                    'property',
                    true,
                    'a%b',
                ],
                'without fulltext' => [
                    [
                        'sql'      => 'select * from `tmp` where (`property` like ?)',
                        'bindings' => [
                            'a\\\\%b%',
                        ],
                    ],
                    'property',
                    false,
                    'a%b',
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
