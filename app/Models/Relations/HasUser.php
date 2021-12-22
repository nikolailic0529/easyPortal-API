<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\User;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin \App\Utils\Eloquent\Model
 */
trait HasUser {
    #[CascadeDelete(false)]
    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function setUserAttribute(User $user): void {
        $this->user()->associate($user);
    }
}
