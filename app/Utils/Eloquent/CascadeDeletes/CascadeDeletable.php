<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\CascadeDeletes;

use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * @deprecated Use {@see \App\Utils\Eloquent\CascadeDeletes\CascadeDelete} instead
 */
interface CascadeDeletable {
    public function isCascadeDeletableRelation(string $name, Relation $relation, bool $default): bool;
}
