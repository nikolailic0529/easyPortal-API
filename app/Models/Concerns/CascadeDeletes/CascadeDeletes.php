<?php declare(strict_types = 1);

namespace App\Models\Concerns\CascadeDeletes;

use Illuminate\Database\Eloquent\Model;

use function app;

/**
 * Cascading SoftDeletes.
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait CascadeDeletes {
    protected static function bootCascadeDeletes(): void {
        static::deleting(static function (Model $model): void {
            app()->make(CascadeProcessor::class)->delete($model);
        });
    }
}
