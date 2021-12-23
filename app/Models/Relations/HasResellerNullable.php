<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Reseller;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin \App\Utils\Eloquent\Model
 */
trait HasResellerNullable {
    #[CascadeDelete(false)]
    public function reseller(): BelongsTo {
        return $this->belongsTo(Reseller::class);
    }

    public function setResellerAttribute(?Reseller $reseller): void {
        $this->reseller()->associate($reseller);
    }
}
