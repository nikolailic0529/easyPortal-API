<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Concerns;

use App\Utils\Eloquent\Callbacks\SetKey;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * @mixin Model
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
