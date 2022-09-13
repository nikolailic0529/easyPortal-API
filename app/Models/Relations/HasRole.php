<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Role;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Model
 */
trait HasRole {
    /**
     * @return BelongsTo<Role, self>
     */
    public function role(): BelongsTo {
        return $this->belongsTo(Role::class);
    }

    public function setRoleAttribute(?Role $role): void {
        $this->role()->associate($role);
    }
}
