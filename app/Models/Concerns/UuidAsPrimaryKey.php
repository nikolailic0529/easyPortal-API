<?php declare(strict_types = 1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

use Illuminate\Support\Str;

use function is_null;

/**
 * @mixin \App\Models\Model
 */
trait UuidAsPrimaryKey {
    /**
     * @inheritdoc
     */
    protected function performInsert(Builder $query) {
        if (!$this->exists && is_null($this->{$this->getKeyName()})) {
            $this->{$this->getKeyName()} = Str::uuid()->toString();
        }

        return parent::performInsert($query);
    }
}
