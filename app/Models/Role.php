<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasOrganizationNullable;
use App\Services\Audit\Concerns\Auditable;
use App\Services\Organization\Eloquent\OwnedByOrganization;
use App\Services\Organization\Eloquent\OwnedByShared;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Concerns\SyncBelongsToMany;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection as BaseCollection;

/**
 * Role.
 *
 * @property string                       $id
 * @property string                       $name
 * @property string|null                  $organization_id
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property Organization|null            $organization
 * @property Collection<int, Permission>  $permissions
 * @property Collection<int, User>        $users
 * @method static \Database\Factories\RoleFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role query()
 * @mixin \Eloquent
 */
class Role extends Model implements Auditable, OwnedByShared {
    use HasFactory;
    use HasOrganizationNullable;
    use SyncBelongsToMany;
    use OwnedByOrganization;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'roles';

    #[CascadeDelete(false)]
    public function users(): BelongsToMany {
        $pivot = new OrganizationUser();

        return $this
            ->belongsToMany(User::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }

    #[CascadeDelete(true)]
    public function permissions(): BelongsToMany {
        $pivot = new RolePermission();

        return $this
            ->belongsToMany(Permission::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }

    /**
     * @param BaseCollection|array<Organization> $permissions
     */
    public function setPermissionsAttribute(BaseCollection|array $permissions): void {
        $this->syncBelongsToMany('permissions', $permissions);
    }
}
