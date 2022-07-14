<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Reseller;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Model
 */
trait HasResellerNullable {
    /**
     * @return BelongsTo<Reseller, self>
     */
    #[CascadeDelete(false)]
    public function reseller(): BelongsTo {
        return $this->belongsTo(Reseller::class);
    }

    public function setResellerAttribute(?Reseller $reseller): void {
        $this->reseller()->associate($reseller);
    }
}
