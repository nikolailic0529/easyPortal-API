<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Concerns;

use App\Utils\Eloquent\Callbacks\SetKey;
use Illuminate\Database\Eloquent\Builder;

/**
 * @mixin \App\Utils\Eloquent\Model
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