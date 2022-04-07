<?php declare(strict_types = 1);

namespace App\Models\Scopes;

use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

use function app;

/**
 * @see \App\Models\Scopes\DocumentType
 *
 * @mixin Model
 */
trait DocumentTypeQuery {
    public function scopeQueryContracts(Builder $builder): Builder {
        app()->make(ContractType::class)->apply($builder, $this);

        return $builder;
    }

    public function scopeQueryQuotes(Builder $builder): Builder {
        app()->make(QuoteType::class)->apply($builder, $this);

        return $builder;
    }
}
