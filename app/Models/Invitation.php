<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasOrganization;
use App\Models\Relations\HasRole;
use App\Models\Relations\HasTeam;
use App\Models\Relations\HasUser;
use App\Services\Organization\Eloquent\OwnedByOrganization;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Model;
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
 * @property ?string                      $team_id
 * @property string                       $email
 * @property \Carbon\CarbonImmutable|null $used_at
 * @property \Carbon\CarbonImmutable      $expired_at
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \App\Models\Organization     $organization
 * @property \App\Models\Role             $role
 * @property \App\Models\User             $sender
 * @property \App\Models\Team|null        $team
 * @property \App\Models\User             $user
 * @method static \Database\Factories\InvitationFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Invitation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Invitation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Invitation query()
 * @mixin \Eloquent
 */
class Invitation extends Model {
    use HasFactory;
    use OwnedByOrganization;
    use HasOrganization;
    use HasUser;
    use HasRole;
    use HasTeam;

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

    #[CascadeDelete(false)]
    public function sender(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function setSenderAttribute(User $sender): void {
        $this->sender()->associate($sender);
    }
}
