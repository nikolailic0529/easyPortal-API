<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method OrganizationUser create($attributes = [], ?Model $parent = null)
 * @method OrganizationUser make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<OrganizationUser>
 */
class OrganizationUserFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = OrganizationUser::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'              => $this->faker->uuid(),
            'organization_id' => Organization::factory(),
            'user_id'         => User::factory(),
            'role_id'         => null,
            'team_id'         => null,
            'enabled'         => true,
            'invited'         => false,
            'created_at'      => Date::now(),
            'updated_at'      => Date::now(),
            'deleted_at'      => null,
        ];
    }
}
