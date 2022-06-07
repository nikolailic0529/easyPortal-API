<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Customers;

use App\GraphQL\Directives\Directives\Aggregated\BuilderValue;
use App\Models\Asset;
use App\Models\Customer;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use InvalidArgumentException;

use function sprintf;

class CustomersAggregated {
    public function __construct(
        protected CurrentOrganization $organization,
    ) {
        // empty
    }

    /**
     * @param BuilderValue<Model> $root
     */
    public function assets(BuilderValue $root): int {
        $builder = $root->getEloquentBuilder();
        $model   = $builder->getModel();
        $assets  = 0;

        if (!($model instanceof Customer)) {
            throw new InvalidArgumentException(sprintf(
                'Expected `%s` model, `%s` given.',
                Customer::class,
                $model::class,
            ));
        }

        if ($this->organization->isRoot()) {
            $assets = (int) $builder
                ->select(new Expression("IFNULL(SUM({$model->qualifyColumn('assets_count')}), 0) as assets"))
                ->toBase()
                ->value('assets');
        } else {
            $assets = Asset::query()
                ->whereIn(
                    $model->assets()->getQualifiedForeignKeyName(),
                    $builder->select($model->getQualifiedKeyName()),
                )
                ->count();
        }

        return $assets;
    }
}
