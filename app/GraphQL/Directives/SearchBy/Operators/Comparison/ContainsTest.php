<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\SearchBy\Operators\Comparison;

use App\GraphQL\Directives\SearchBy\Metadata;
use Closure;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery;
use Mockery\MockInterface;
use Tests\DataProviders\Builders\BuilderDataProvider;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Directives\SearchBy\Operators\Comparison\Contains
 */
class ContainsTest extends TestCase {
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
        $this->override(Metadata::class, static function (MockInterface $mock) use ($property, $fulltext): void {
            $mock
                ->shouldReceive('isFulltextIndexExists')
                ->with(Mockery::any(), $property)
                ->once()
                ->andReturn($fulltext);
        });

        $operator = $this->app->make(Contains::class);
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
                        'sql'      => 'select * from `tmp` where (`property` like ? and MATCH(property) AGAINST (?))',
                        'bindings' => [
                            '%a\\\\%b%',
                            'a%b',
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
                            '%a\\\\%b%',
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
