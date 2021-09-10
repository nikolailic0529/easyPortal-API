<?php declare(strict_types = 1);

namespace App\Models;

use App\Services\Organization\Eloquent\OwnedByOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Invitation.
 *
 * @property string                       $id
 * @property string                       $organization_id
 * @property string                       $sender_id
 * @property string                       $user_id
 * @property string                       $role_id
 * @property string                       $email
 * @property \Carbon\CarbonImmutable|null $used_at
 * @property \Carbon\CarbonImmutable      $expired_at
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Models\Role        $role
 * @property-read \App\Models\User        $user
 * @method static \Database\Factories\InvitationFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Invitation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Invitation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Invitation query()
 * @mixin \Eloquent
 */
class Invitation extends Model {
    use HasFactory;
    use OwnedByOrganization;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'invitations';

    protected const CASTS = [
        'used_at'    => 'datetime',
        'expired_at' => 'datetime',
    ] + parent::CASTS;

    /**
     * The attributes that should be cast to native types.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $casts = self::CASTS;

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo {
        return $this->belongsTo(Role::class);
    }
}
