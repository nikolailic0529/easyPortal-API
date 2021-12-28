<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasOrganization;
use App\Services\Audit\Concerns\Auditable;
use App\Services\Organization\Eloquent\OwnedByOrganization;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Model;
use App\Utils\Eloquent\SmartSave\Upsertable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Organization User (pivot)
 *
 * @property string                       $id
 * @property string                       $organization_id
 * @property string                       $user_id
 * @property string|null                  $role_id
 * @property string|null                  $team_id
 * @property bool                         $enabled
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \App\Models\Organization     $organization
 * @property \App\Models\Role|null        $role
 * @property \App\Models\Team|null        $team
 * @method static \Database\Factories\OrganizationUserFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrganizationUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrganizationUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrganizationUser query()
 * @mixin \Eloquent
 */
class OrganizationUser extends Model implements Auditable, Upsertable {
    use HasFactory;
    use OwnedByOrganization;
    use HasOrganization;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'organization_users';

    protected const CASTS = [
        'enabled' => 'bool',
    ] + parent::CASTS;

    #[CascadeDelete(false)]
    public function role(): BelongsTo {
        return $this->belongsTo(Role::class);
    }

    public function setRoleAttribute(?Role $role): void {
        $this->role()->associate($role);
    }

    #[CascadeDelete(false)]
    public function team(): BelongsTo {
        return $this->belongsTo(Team::class);
    }

    public function setTeamAttribute(?Team $team): void {
        $this->team()->associate($team);
    }

    /**
     * @inheritDoc
     */
    public static function getUniqueKey(): array {
        return ['organization_id', 'user_id'];
    }
}
