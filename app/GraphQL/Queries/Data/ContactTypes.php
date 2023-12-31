<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Data;

use App\Models\Contact;
use App\Models\Data\Type;
use Illuminate\Database\Eloquent\Builder;

class ContactTypes {
    public function __invoke(): Builder {
        return Type::query()
            ->where('object_type', '=', (new Contact())->getMorphClass())
            ->orderByKey();
    }
}
