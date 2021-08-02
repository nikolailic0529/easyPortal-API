<?php declare(strict_types = 1);

namespace App\Models\Concerns\Relations;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin \App\Models\Model
 */
trait HasOrganization {
    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class);
    }

    public function setOrganizationAttribute(?Organization $organization): void {
        $this->organization()->associate($organization);
    }
}
