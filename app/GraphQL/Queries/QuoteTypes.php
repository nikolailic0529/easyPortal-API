<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Document;
use App\Models\Type;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Collection;

class QuoteTypes {
    public function __construct(
        protected Repository $config,
    ) {
        // empty
    }
    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     */
    public function __invoke($_, array $args): Collection {
        $quotesTypes = $this->config->get('ep.quote_types');
        $builder     = Type::query()
            ->where('object_type', '=', (new Document())->getMorphClass())
            ->orderByKey();
        // if empty quotes type we will use ids not represented in contracts
        if (empty($quotesTypes)) {
            $builder->whereNotIn('id', $this->config->get('ep.contract_types'));
        } else {
            $builder->whereIn('id', $quotesTypes);
        }
        return $builder->get();
    }
}
