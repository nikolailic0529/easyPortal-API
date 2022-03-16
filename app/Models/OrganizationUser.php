<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasOrganization;
use App\Models\Relations\HasRole;
use App\Models\Relations\HasTeam;
use App\Models\Relations\HasUser;
use App\Services\Audit\Concerns\Auditable;
use App\Services\Organization\Eloquent\OwnedByOrganization;
use App\Utils\Eloquent\Model;
use App\Utils\Eloquent\SmartSave\Upsertable;
use Carbon\CarbonImmutable;
use Database\Factories\OrganizationUserFactory;
use Eloquent;
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
 * @mixin Eloquent
 */
class OrganizationUser extends Model implements Auditable, Upsertable {
    use HasFactory;
    use OwnedByOrganization;
    use HasOrganization;
    use HasUser;
    use HasRole;
    use HasTeam;

    protected const CASTS = [
        'enabled' => 'bool',
        'invited' => 'bool',
    ] + parent::CASTS;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'organization_users';

    /**
     * The attributes that should be cast to native types.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $casts = self::CASTS;

    /**
     * @inheritDoc
     */
    public static function getUniqueKey(): array {
        return ['organization_id', 'user_id'];
    }
}
