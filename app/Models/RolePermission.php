<?php declare(strict_types = 1);

namespace App\Models;

use App\Utils\Eloquent\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Role Permission (pivot)
 *
 * @property string                       $id
 * @property string                       $role_id
 * @property string                       $permission_id
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @method static \Database\Factories\RolePermissionFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RolePermission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RolePermission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RolePermission query()
 * @mixin \Eloquent
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
