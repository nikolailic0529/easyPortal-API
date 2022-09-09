<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Quotes;

use App\Models\Data\Type;
use App\Models\Document;
use Illuminate\Database\Eloquent\Builder;

class QuoteTypes {
    public function __invoke(): Builder {
        return Type::query()
            ->queryQuotes()
            ->where('object_type', '=', (new Document())->getMorphClass())
            ->orderByKey();
    }
}
