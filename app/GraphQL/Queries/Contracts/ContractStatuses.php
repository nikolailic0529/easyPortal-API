<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Contracts;

use App\Models\Document;
use App\Models\Status;
use Illuminate\Database\Eloquent\Builder;

class ContractStatuses {
    public function __invoke(): Builder {
        return Status::query()
            ->where('object_type', '=', (new Document())->getMorphClass())
            ->orderByKey();
    }
}
