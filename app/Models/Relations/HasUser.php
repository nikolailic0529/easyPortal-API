<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\User;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Model
 *
 * @property User $user
 */
trait HasUser {
    /**
     * @return BelongsTo<User, self>
     */
    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function setUserAttribute(User $user): void {
        $this->user()->associate($user);
    }
}
