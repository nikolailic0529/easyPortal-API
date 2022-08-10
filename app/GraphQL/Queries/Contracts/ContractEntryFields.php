<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Contracts;

use App\Models\DocumentEntryField;
use App\Models\Field;
use Illuminate\Database\Eloquent\Builder;

class ContractEntryFields {
    public function __construct() {
        // empty
    }

    /**
     * @return Builder<Field>
     */
    public function __invoke(): Builder {
        return Field::query()
            ->where('object_type', '=', (new DocumentEntryField())->getMorphClass())
            ->orderByKey();
    }
}
