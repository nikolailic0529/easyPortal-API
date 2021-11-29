<?php declare(strict_types = 1);

namespace App\Models\Scopes;

use App\Models\Type;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Builder;

use function app;

/**
 * @see \App\Models\Scopes\DocumentType
 *
 * @mixin \App\Utils\Eloquent\Model
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

    public function scopeQueryDocuments(Builder $builder): Builder {
        return $builder->where(function (Builder $builder): void {
            $app    = app();
            $gate   = $app->make(Gate::class);
            $empty  = true;
            $scopes = [
                ContractType::class => ['contracts-view', 'customers-view'],
                QuoteType::class    => ['quotes-view', 'customers-view'],
            ];

            foreach ($scopes as $scope => $permissions) {
                if ($gate->any($permissions)) {
                    $builder->orWhere(function (Builder $builder) use ($app, $scope): void {
                        $app->make($scope)->apply($builder, $this);
                    });

                    $empty = false;
                }
            }

            if ($empty) {
                $model = $builder->newModelInstance();
                $key   = $model instanceof Type ? $model->getKeyName() : 'type_id';

                $builder->where($key, '=', 'unknown');
            }
        });
    }
}
