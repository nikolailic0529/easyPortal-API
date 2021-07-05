<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Document;
use App\Models\Type;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;

class ContractTypes {
    public function __construct(
        protected Repository $config,
    ) {
        // empty
    }

    public function __invoke(): Builder {
        $builder = Type::query()->where('object_type', '=', (new Document())->getMorphClass());
        $builder = $this->prepare($builder, (new Type())->getKeyName());

        return $builder;
    }

    public function prepare(Builder $builder, string $key = 'type_id'): Builder {
        return $builder->whereIn($key, $this->config->get('ep.contract_types'));
    }
}
