<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Document;
use App\Models\Type;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;

class QuoteTypes {
    public function __construct(
        protected Repository $config,
    ) {
        // empty
    }

    public function __invoke(): Builder {
        $builder = Type::query()->where('object_type', '=', (new Document())->getMorphClass())->orderByKey();
        $builder = $this->prepare($builder, (new Type())->getKeyName());

        return $builder;
    }

    public function prepare(Builder $builder, string $key = 'type_id'): Builder {
        // if empty quotes type we will use ids not represented in contracts
        $contractTypes = $this->config->get('ep.contract_types');
        $quoteTypes    = $this->config->get('ep.quote_types');

        if ($quoteTypes) {
            $builder->whereIn($key, $quoteTypes);
        } elseif ($contractTypes) {
            $builder->whereNotIn($key, $contractTypes);
        } else {
            $builder->whereIn($key, []);
        }

        return $builder;
    }
}
