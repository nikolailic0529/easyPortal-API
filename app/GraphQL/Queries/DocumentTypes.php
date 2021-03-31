<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Document;
use App\Models\Type;
use Illuminate\Support\Collection;

class DocumentTypes {
    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     */
    public function __invoke($_, array $args): Collection {
        return Type::query()
            ->where('object_type', '=', (new Document())->getMorphClass())
            ->orderByKey()
            ->get();
    }
}
