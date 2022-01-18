<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Role;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin \App\Utils\Eloquent\Model
 */
trait HasRole {
    #[CascadeDelete(false)]
    public function role(): BelongsTo {
        return $this->belongsTo(Role::class);
    }

    public function setRoleAttribute(?Role $role): void {
        $this->role()->associate($role);
    }
}