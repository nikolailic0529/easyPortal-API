<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Data\Team;
use App\Models\Relations\HasOrganization;
use App\Models\Relations\HasRole;
use App\Models\Relations\HasTeam;
use App\Models\Relations\HasUser;
use App\Services\Organization\Eloquent\OwnedByOrganization;
use App\Services\Organization\Eloquent\OwnedByOrganizationImpl;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\InvitationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Invitation.
 *
 * @property string               $id
 * @property string               $organization_id
 * @property string               $sender_id
 * @property string               $user_id
 * @property string               $role_id
 * @property ?string              $team_id
 * @property string               $email
 * @property CarbonImmutable|null $used_at
 * @property CarbonImmutable      $expired_at
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property Organization         $organization
 * @property Role                 $role
 * @property User                 $sender
 * @property Team|null            $team
 * @property User                 $user
 * @method static InvitationFactory factory(...$parameters)
 * @method static Builder|Invitation newModelQuery()
 * @method static Builder|Invitation newQuery()
 * @method static Builder|Invitation query()
 */
class Invitation extends Model implements OwnedByOrganization {
    use HasFactory;
    use OwnedByOrganizationImpl;
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

    /**
     * @return BelongsTo<User, self>
     */
    public function sender(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function setSenderAttribute(User $sender): void {
        $this->sender()->associate($sender);
    }
}
