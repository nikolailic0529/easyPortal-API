<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Contracts;

use App\Models\Data\Type;
use App\Models\Document;
use Illuminate\Database\Eloquent\Builder;

class ContractTypes {
    public function __invoke(): Builder {
        return Type::query()
            ->queryContracts()
            ->where('object_type', '=', (new Document())->getMorphClass())
            ->orderByKey();
    }
}
