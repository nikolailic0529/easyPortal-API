<?php declare(strict_types = 1);

namespace App\Models\Concerns;

use App\Models\Callbacks\SetKey;
use Illuminate\Database\Eloquent\Builder;

/**
 * @mixin \App\Models\Model
 */
trait UuidAsPrimaryKey {
    /**
     * @inheritdoc
     */
    protected function performInsert(Builder $query) {
        (new SetKey())($this);

        return parent::performInsert($query);
    }
}
