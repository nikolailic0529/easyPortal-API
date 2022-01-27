<?php declare(strict_types = 1);

namespace App\GraphQL\Extensions\LaraAsp\SearchBy\Operators\Comparison;

use App\GraphQL\Extensions\LaraAsp\SearchBy\Metadata;
use Closure;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery;
use Mockery\MockInterface;
use Tests\DataProviders\Builders\BuilderDataProvider;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Extensions\LaraAsp\SearchBy\Operators\Comparison\EndsWith
 */
class EndsWithTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::apply
     *
     * @dataProvider dataProviderApply
     *
     * @param array{query: string, bindings: array<mixed>} $expected
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

        $operator = $this->app->make(EndsWith::class);
        $builder  = $builder($this);
        $builder  = $operator->apply($builder, $property, $value);

        $this->assertDatabaseQueryEquals($expected, $builder);
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
                        'query'    => <<<'SQL'
                            select *
                            from `tmp`
                            where (
                                `property` LIKE ? ESCAPE '!' and MATCH(property) AGAINST (?)
                                )
                            SQL
                        ,
                        'bindings' => [
                            '%a!%b',
                            'a%b',
                        ],
                    ],
                    'property',
                    true,
                    'a%b',
                ],
                'without fulltext' => [
                    [
                        'query'    => 'select * from `tmp` where `property` LIKE ? ESCAPE \'!\'',
                        'bindings' => [
                            '%a!%b',
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
