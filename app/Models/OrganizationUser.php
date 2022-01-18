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
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Organization User (pivot)
 *
 * @property string                       $id
 * @property string                       $organization_id
 * @property string                       $user_id
 * @property string|null                  $role_id
 * @property string|null                  $team_id
 * @property bool                         $enabled
 * @property bool                         $invited
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \App\Models\Organization     $organization
 * @property \App\Models\Role|null        $role
 * @property \App\Models\Team|null        $team
 * @property \App\Models\User             $user
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
