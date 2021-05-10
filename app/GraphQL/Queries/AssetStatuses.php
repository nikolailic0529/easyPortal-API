<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Asset;
use App\Models\Status;
use Illuminate\Support\Collection;

class AssetStatuses {
    /**
     * @param array<string, mixed> $args
     */
    public function __invoke(mixed $_, array $args): Collection {
        return Status::query()
            ->where('object_type', '=', (new Asset())->getMorphClass())
            ->orderByKey()
            ->get();
    }
}
