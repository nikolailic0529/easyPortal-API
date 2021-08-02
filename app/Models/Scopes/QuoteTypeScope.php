<?php declare(strict_types = 1);

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;

use function app;

/**
 * @see \App\Models\Scopes\QuoteType
 *
 * @mixin \App\Models\Model
 */
trait QuoteTypeScope {
    public function scopeQueryQuotes(Builder $builder): Builder {
        app()->make(QuoteType::class)->apply($builder, $this);

        return $builder;
    }
}
