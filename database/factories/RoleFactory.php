<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Role;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method Role create($attributes = [], ?Model $parent = null)
 * @method Role make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<Role>
 */
class RoleFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = Role::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'              => $this->faker->uuid(),
            'organization_id' => null,
            'name'            => $this->faker->name(),
            'created_at'      => Date::now(),
            'updated_at'      => Date::now(),
            'deleted_at'      => null,
        ];
    }
}
