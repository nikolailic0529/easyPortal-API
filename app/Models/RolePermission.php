<?php declare(strict_types = 1);

namespace App\Models;

use App\Utils\Eloquent\Pivot;
use Carbon\CarbonImmutable;
use Database\Factories\RolePermissionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Role Permission (pivot)
 *
 * @property string               $id
 * @property string               $role_id
 * @property string               $permission_id
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @method static RolePermissionFactory factory(...$parameters)
 * @method static Builder<RolePermission>|RolePermission newModelQuery()
 * @method static Builder<RolePermission>|RolePermission newQuery()
 * @method static Builder<RolePermission>|RolePermission query()
 */
class RolePermission extends Pivot {
    use HasFactory;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'role_permissions';
}
