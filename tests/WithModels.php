<?php declare(strict_types = 1);

namespace Tests;

use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin TestCase
 */
trait WithModels {
    /**
     * @param array<class-string<Model>,int> $expected
     */
    protected static function assertModelsCount(array $expected): void {
        $actual = [];

        foreach ($expected as $model => $count) {
            $actual[$model] = GlobalScopes::callWithoutAll(static function () use ($model): int {
                return $model::query()->count();
            });
        }

        self::assertEquals($expected, $actual);
    }
}
