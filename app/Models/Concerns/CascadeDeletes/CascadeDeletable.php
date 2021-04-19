<?php declare(strict_types = 1);

namespace App\Models\Concerns\CascadeDeletes;

use Illuminate\Database\Eloquent\Relations\Relation;

interface CascadeDeletable {
    public function isCascadeDeletableRelation(string $name, Relation $relation, bool $default): bool;
}
