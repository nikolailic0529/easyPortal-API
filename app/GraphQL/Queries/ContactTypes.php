<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Builder;

class ContactTypes {
    public function __invoke(Builder $builder): Builder {
        return $builder->where('object_type', '=', (new Contact())->getMorphClass());
    }
}
