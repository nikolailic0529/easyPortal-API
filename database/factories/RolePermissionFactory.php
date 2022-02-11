<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

/**
 * @method \App\Models\RolePermission create($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 * @method \App\Models\RolePermission make($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 */
class RolePermissionFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = RolePermission::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        return [
            'id'            => $this->faker->uuid,
            'role_id'       => static function (): Role {
                return Role::factory()->create();
            },
            'permission_id' => static function (): Permission {
                return Permission::factory()->create();
            },
            'created_at'    => Date::now(),
            'updated_at'    => Date::now(),
            'deleted_at'    => null,
        ];
    }
}
