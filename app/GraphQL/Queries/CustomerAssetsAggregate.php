<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Asset;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as DatabaseBuilder;
use InvalidArgumentException;

use function sprintf;

class CustomerAssetsAggregate extends AssetsAggregate {
    protected function getQuery(mixed $root): DatabaseBuilder|EloquentBuilder {
        if (!($root instanceof Customer)) {
            throw new InvalidArgumentException(sprintf(
                'Root should be instance of `%s`.',
                Customer::class,
            ));
        }
        $query = parent::prepareQuery();
        return $query->where((new Asset())->qualifyColumn('customer_id'), '=', $root->getKey());
    }
}
