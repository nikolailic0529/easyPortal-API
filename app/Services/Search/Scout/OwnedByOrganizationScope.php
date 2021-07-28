<?php declare(strict_types = 1);

namespace App\Services\Search\Scout;

use App\Services\Organization\CurrentOrganization;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope as EloquentOwnedByOrganizationScope;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Builder;

/**
 * @see \App\Services\Organization\Eloquent\OwnedByOrganization
 */
class OwnedByOrganizationScope {
    public const KEY = 'owned_by_organizations';

    public function __construct(
        protected CurrentOrganization $organization,
    ) {
        // empty
    }

    public function apply(Builder $builder, Model $model): void {
        // Has scope?
        if ($model->hasGlobalScope(EloquentOwnedByOrganizationScope::class)) {
            return;
        }

        // Root organization can view all data
        if ($this->organization->isRoot()) {
            return;
        }

        // Conditions
        $builder->where(self::KEY, $this->organization->getKey());
    }
}
