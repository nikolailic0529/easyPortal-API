<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Documents;

use App\Models\Document;
use App\Models\Type;
use Illuminate\Database\Eloquent\Builder;

class DocumentTypes {
    public function __invoke(): Builder {
        return Type::query()
            ->queryDocuments()
            ->where('object_type', '=', (new Document())->getMorphClass())
            ->orderByKey();
    }
}
