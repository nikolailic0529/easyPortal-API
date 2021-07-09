<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Asset;
use App\Models\Type;
use Illuminate\Database\Eloquent\Builder;

class AssetTypes {
    public function __invoke(): Builder {
        return Type::query()
            ->where('object_type', '=', (new Asset())->getMorphClass())
            ->orderByKey();
    }
}
