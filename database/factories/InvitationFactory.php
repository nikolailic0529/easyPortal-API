<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Invitation;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method Invitation create($attributes = [], ?Model $parent = null)
 * @method Invitation make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<Invitation>
 */
class InvitationFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = Invitation::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'              => $this->faker->uuid(),
            'organization_id' => Organization::factory(),
            'user_id'         => User::factory(),
            'sender_id'       => User::factory(),
            'role_id'         => Role::factory(),
            'team_id'         => null,
            'email'           => $this->faker->unique()->safeEmail(),
            'used_at'         => null,
            'expired_at'      => Date::now()->addHour(),
            'created_at'      => Date::now(),
            'updated_at'      => Date::now(),
            'deleted_at'      => null,
        ];
    }
}
