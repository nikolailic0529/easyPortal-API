<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\ChangeRequest;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @mixin Model
 *
 * @property-read Collection<int, ChangeRequest> $changeRequests
 */
trait HasChangeRequests {
    /**
     * @return MorphMany<ChangeRequest>
     */
    #[CascadeDelete]
    public function changeRequests(): MorphMany {
        return $this->morphMany(ChangeRequest::class, 'object');
    }
}
