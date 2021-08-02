<?php declare(strict_types = 1);

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;

use function app;

/**
 * @see \App\Models\Scopes\ContractType
 *
 * @mixin \App\Models\Model
 */
trait ContractTypeScope {
    public function scopeQueryContracts(Builder $builder): Builder {
        app()->make(ContractType::class)->apply($builder, $this);

        return $builder;
    }
}
