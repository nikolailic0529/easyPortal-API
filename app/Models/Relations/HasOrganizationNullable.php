<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Organization;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Model
 */
trait HasOrganizationNullable {
    /**
     * @return BelongsTo<Organization, self>
     */
    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class);
    }

    public function setOrganizationAttribute(?Organization $organization): void {
        $this->organization()->associate($organization);
    }
}
