<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\CascadeDeletes;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;

/**
 * Cascading SoftDeletes.
 *
 * @mixin Model
 */
trait CascadeDeletes {
    public function delete(): bool {
        return Container::getInstance()->make(CascadeProcessor::class)->delete($this)
            && parent::delete();
    }
}
