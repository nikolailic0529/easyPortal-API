<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Document;
use App\Models\Type;
use Illuminate\Database\Eloquent\Builder;

class ContractTypes {
    public function __invoke(): Builder {
        return Type::query()
            ->queryContracts()
            ->where('object_type', '=', (new Document())->getMorphClass())
            ->orderByKey();
    }
}
