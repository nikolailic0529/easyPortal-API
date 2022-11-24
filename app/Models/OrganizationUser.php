<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Data\Team;
use App\Models\Relations\HasOrganization;
use App\Models\Relations\HasRole;
use App\Models\Relations\HasTeam;
use App\Models\Relations\HasUser;
use App\Services\Audit\Contracts\Auditable;
use App\Services\Audit\Traits\AuditableImpl;
use App\Services\Organization\Eloquent\OwnedByOrganization;
use App\Services\Organization\Eloquent\OwnedByOrganizationImpl;
use App\Utils\Eloquent\Pivot;
use App\Utils\Eloquent\SmartSave\Upsertable;
use Carbon\CarbonImmutable;
use Database\Factories\OrganizationUserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Organization User (pivot)
 *
 * @property string               $id
 * @property string               $organization_id
 * @property string               $user_id
 * @property string|null          $role_id
 * @property string|null          $team_id
 * @property bool                 $enabled
 * @property bool                 $invited
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property Organization         $organization
 * @property Role|null            $role
 * @property Team|null            $team
 * @property User                 $user
 * @method static OrganizationUserFactory factory(...$parameters)
 * @method static Builder|OrganizationUser newModelQuery()
 * @method static Builder|OrganizationUser newQuery()
 * @method static Builder|OrganizationUser query()
 */
class OrganizationUser extends Pivot implements OwnedByOrganization, Auditable, Upsertable {
    use HasFactory;
    use AuditableImpl;
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
    protected $table = 'organization_users';

    /**
     * The attributes that should be cast to native types.
     *
     * @inheritdoc
     */
    protected $casts = [
        'enabled' => 'bool',
        'invited' => 'bool',
    ];

    /**
     * @inheritDoc
     */
    public static function getUniqueKey(): array {
        return ['organization_id', 'user_id'];
    }
}
