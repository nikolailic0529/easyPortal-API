<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\CascadeDeletes;

use Illuminate\Database\Eloquent\Model;

use function app;

/**
 * Cascading SoftDeletes.
 *
 * @mixin Model
 */
trait CascadeDeletes {
    public function delete(): bool {
        return app()->make(CascadeProcessor::class)->delete($this)
            && parent::delete();
    }
}
