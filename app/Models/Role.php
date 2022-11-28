<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasOrganizationNullable;
use App\Services\Audit\Contracts\Auditable;
use App\Services\Audit\Traits\AuditableImpl;
use App\Services\Organization\Eloquent\OwnedByOrganization;
use App\Services\Organization\Eloquent\OwnedByOrganizationImpl;
use App\Services\Organization\Eloquent\OwnedByShared;
use App\Utils\Eloquent\Concerns\SyncBelongsToMany;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Role.
 *
 * @property string                      $id
 * @property string                      $name
 * @property string|null                 $organization_id
 * @property CarbonImmutable             $created_at
 * @property CarbonImmutable             $updated_at
 * @property CarbonImmutable|null        $deleted_at
 * @property Organization|null           $organization
 * @property Collection<int, Permission> $permissions
 * @property-read  Collection<int, User> $users
 * @method static RoleFactory factory(...$parameters)
 * @method static Builder|Role newModelQuery()
 * @method static Builder|Role newQuery()
 * @method static Builder|Role query()
 */
class Role extends Model implements OwnedByOrganization, Auditable, OwnedByShared {
    use HasFactory;
    use AuditableImpl;
    use HasOrganizationNullable;
    use SyncBelongsToMany;
    use OwnedByOrganizationImpl;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'roles';

    /**
     * @return BelongsToMany<User>
     */
    public function users(): BelongsToMany {
        $pivot = new OrganizationUser();

        return $this
            ->belongsToMany(User::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Permission>
     */
    public function permissions(): BelongsToMany {
        $pivot = new RolePermission();

        return $this
            ->belongsToMany(Permission::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }

    /**
     * @param Collection<int, Permission> $permissions
     */
    public function setPermissionsAttribute(Collection $permissions): void {
        $this->syncBelongsToMany('permissions', $permissions);
    }
}
