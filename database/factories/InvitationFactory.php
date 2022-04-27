<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Invitation;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

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
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = Invitation::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        return [
            'id'              => $this->faker->uuid(),
            'organization_id' => static function (): Organization {
                return Organization::factory()->create();
            },
            'user_id'         => static function (): User {
                return User::factory()->create();
            },
            'sender_id'       => static function (): User {
                return User::factory()->create();
            },
            'role_id'         => static function (): Role {
                return Role::factory()->create();
            },
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
