<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method RolePermission create($attributes = [], ?Model $parent = null)
 * @method RolePermission make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<RolePermission>
 */
class RolePermissionFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = RolePermission::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'            => $this->faker->uuid(),
            'role_id'       => Role::factory(),
            'permission_id' => Permission::factory(),
            'created_at'    => Date::now(),
            'updated_at'    => Date::now(),
            'deleted_at'    => null,
        ];
    }
}
