<?php declare(strict_types = 1);

namespace App\Services\Organization\Eloquent;

use App\Services\Organization\CurrentOrganization;
use App\Services\Search\Builders\Builder;
use App\Services\Search\Contracts\ScopeWithMetadata;
use App\Services\Search\Properties\Properties;
use App\Services\Search\Properties\Uuid;
use App\Utils\Eloquent\GlobalScopes\DisableableScope;
use App\Utils\Eloquent\ModelProperty;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends DisableableScope<TModel>
 * @implements ScopeWithMetadata<TModel>
 */
class OwnedByOrganizationScope extends DisableableScope implements ScopeWithMetadata {
    protected const SEARCH_METADATA_PREFIX       = 'owners';
    protected const SEARCH_METADATA_UNKNOWN      = 'unknown';
    protected const SEARCH_METADATA_ORGANIZATION = 'organization';
    protected const SEARCH_METADATA_RESELLER     = 'reseller';

    public function __construct(
        protected CurrentOrganization $organization,
    ) {
        // empty
    }

    // <editor-fold desc="Eloquent">
    // =========================================================================
    protected function handle(EloquentBuilder $builder, Model $model): void {
        // Root organization can view all data
        if ($this->organization->isRoot()) {
            return;
        }

        // OwnedBy?
        if (!($model instanceof OwnedBy)) {
            return;
        }

        // Hide data related to another organization
        $property     = $this->getProperty($this->organization, $model);
        $organization = $this->organization->getKey();

        if ($property === null) {
            $builder->whereRaw('0 = 1');
        } elseif ($property->isRelation()) {
            /** @noinspection PhpExpressionResultUnusedInspection */
            $builder->whereHasIn(
                $property->getRelationName(),
                static function (EloquentBuilder $builder) use ($property, $organization): void {
                    $builder->where($builder->getModel()->qualifyColumn($property->getName()), '=', $organization);
                },
            );
        } else {
            $builder->where(static function (EloquentBuilder $builder) use ($model, $property, $organization): void {
                $builder->orWhere($model->qualifyColumn($property->getName()), '=', $organization);

                if ($model instanceof OwnedByShared) {
                    $builder->orWhereNull($model->qualifyColumn($property->getName()));
                }
            });
        }
    }
    // </editor-fold>

    // <editor-fold desc="Search">
    // =========================================================================
    protected function handleForSearch(Builder $builder, Model $model): void {
        /** TODO {@link \App\Services\Organization\Eloquent\OwnedByShared} support? */

        // Root organization can view all data
        if ($this->organization->isRoot()) {
            return;
        }

        // OwnedBy?
        if (!($model instanceof OwnedBy)) {
            return;
        }

        // Hide data related to another organization
        $prefix       = self::SEARCH_METADATA_PREFIX;
        $property     = self::SEARCH_METADATA_UNKNOWN;
        $organization = $this->organization->getKey();

        if ($model instanceof OwnedByOrganization) {
            $property = self::SEARCH_METADATA_ORGANIZATION;
        } elseif ($model instanceof OwnedByReseller) {
            $property = self::SEARCH_METADATA_RESELLER;
        } else {
            // empty
        }

        $builder->whereMetadata("{$prefix}.{$property}", $organization);
    }

    /**
     * @inheritDoc
     */
    public function getSearchMetadata(Model $model): array {
        $owners = [];

        if ($model instanceof OwnedByOrganization) {
            $owners[self::SEARCH_METADATA_ORGANIZATION] = new Uuid($model::getOwnedByOrganizationColumn());
        }

        if ($model instanceof OwnedByReseller) {
            $owners[self::SEARCH_METADATA_RESELLER] = new Uuid($model::getOwnedByResellerColumn());
        }

        return $owners
            ? [self::SEARCH_METADATA_PREFIX => new Properties($owners)]
            : [];
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    public static function getProperty(CurrentOrganization $organization, Model $model): ?ModelProperty {
        $property = null;

        if ($model instanceof OwnedByOrganization) {
            $property = new ModelProperty($model::getOwnedByOrganizationColumn());
        } elseif ($model instanceof OwnedByReseller) {
            $property = new ModelProperty($model::getOwnedByResellerColumn());
        } else {
            // empty
        }

        return $property;
    }
    // </editor-fold>
}
