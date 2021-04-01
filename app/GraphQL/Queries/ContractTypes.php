<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Type;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Collection;

class ContractTypes {
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
        return Type::whereIn('id', $this->config->get('easyportal.contract_types'))->get();
    }
}
