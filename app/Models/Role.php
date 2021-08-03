<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\Relations\HasOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Role.
 *
 * @property string                       $id
 * @property string                       $name
 * @property string                       $organization_id
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \App\Models\Organization     $organization
 * @method static \Database\Factories\RoleFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role query()
 * @mixin \Eloquent
 */
class Role extends Model {
    use HasFactory;
    use HasOrganization;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'roles';
}
