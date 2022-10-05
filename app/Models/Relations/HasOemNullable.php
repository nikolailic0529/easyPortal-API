<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Data\Oem;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Model
 */
trait HasOemNullable {
    /**
     * @return BelongsTo<Oem, self>
     */
    public function oem(): BelongsTo {
        return $this->belongsTo(Oem::class);
    }

    public function setOemAttribute(?Oem $oem): void {
        $this->oem()->associate($oem);
    }
}
