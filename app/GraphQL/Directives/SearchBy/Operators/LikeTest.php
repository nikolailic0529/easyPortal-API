<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\SearchBy\Operators;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Directives\SearchBy\Operators\Like
 */
class LikeTest extends TestCase {
    /**
     * @covers ::escape
     */
    public function testEscape(): void {
        $expected = "a \\\\\\\\ b \n c \\\\% d \\\\_ e";
        $string   = "a \\ b \n c % d _ e";
        $like     = new class() extends Like {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            public function getName(): string {
                return __FUNCTION__;
            }

            protected function getDescription(): string {
                return __FUNCTION__;
            }

            public function apply(
                EloquentBuilder|QueryBuilder $builder,
                string $property,
                mixed $value,
            ): EloquentBuilder|QueryBuilder {
                return $builder;
            }

            protected function value(string $string): string {
                return $string;
            }

            public function escape(string $string): string {
                return parent::escape($string);
            }
        };

        $this->assertEquals($expected, $like->escape($string));
    }
}
