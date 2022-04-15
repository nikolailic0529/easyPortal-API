<?php declare(strict_types = 1);

namespace App\Models\Scopes;

use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

use function app;

/**
 * @see \App\Models\Scopes\DocumentType
 *
 * @mixin Model
 *
 * @template TModel of \App\Models\Document|\App\Models\Type
 *
 * @method Builder<TModel> queryContracts()
 * @method Builder<TModel> queryDocuments()
 * @method Builder<TModel> queryQuotes()
 */
trait DocumentTypeQuery {
    /**
     * @template T of Builder<TModel>
     *
     * @param T $builder
     *
     * @return T
     */
    public function scopeQueryContracts(Builder $builder): Builder {
        app()->make(ContractType::class)->apply($builder, $this);

        return $builder;
    }

    /**
     * @template T of Builder<TModel>
     *
     * @param T $builder
     *
     * @return T
     */
    public function scopeQueryQuotes(Builder $builder): Builder {
        app()->make(QuoteType::class)->apply($builder, $this);

        return $builder;
    }

    /**
     * @template T of Builder<TModel>
     *
     * @param T $builder
     *
     * @return T
     */
    public function scopeQueryDocuments(Builder $builder): Builder {
        return $builder->where(function (Builder $builder): void {
            $builder->orWhere(function (Builder $builder): void {
                $this->scopeQueryContracts($builder);
            });
            $builder->orWhere(function (Builder $builder): void {
                $this->scopeQueryQuotes($builder);
            });
        });
    }
}
