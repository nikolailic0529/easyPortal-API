<?php declare(strict_types = 1);

namespace App\Models\Scopes;

use App\Models\Document;
use App\Utils\Eloquent\Model;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Builder;

/**
 * @see DocumentTypeScope
 *
 * @mixin Model
 *
 * @method Builder<Document> queryContracts()
 * @method Builder<Document> queryDocuments()
 * @method Builder<Document> queryQuotes()
 */
trait DocumentTypeQueries {
    /**
     * @template T of Builder<Document>
     *
     * @param T $builder
     *
     * @return T
     */
    public function scopeQueryContracts(Builder $builder): Builder {
        Container::getInstance()->make(DocumentTypeContractScope::class)->apply($builder, $this);

        return $builder;
    }

    /**
     * @template T of Builder<Document>
     *
     * @param T $builder
     *
     * @return T
     */
    public function scopeQueryQuotes(Builder $builder): Builder {
        Container::getInstance()->make(DocumentTypeQuoteType::class)->apply($builder, $this);

        return $builder;
    }

    /**
     * @template T of Builder<Document>
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
