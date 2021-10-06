<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Assets;

use App\Models\Asset;
use App\Models\Status;
use Illuminate\Database\Eloquent\Builder;

class AssetStatuses {
    public function __invoke(): Builder {
        return Status::query()
            ->where('object_type', '=', (new Asset())->getMorphClass())
            ->orderByKey();
    }
}
