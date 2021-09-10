<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Invitation;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

/**
 * @method \App\Models\Invitation create($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 * @method \App\Models\Invitation make($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
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
            'id'              => $this->faker->uuid,
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
            'email'           => $this->faker->unique()->safeEmail,
            'used_at'         => null,
            'expired_at'      => Date::now()->addHour(),
            'created_at'      => Date::now(),
            'updated_at'      => Date::now(),
            'deleted_at'      => null,
        ];
    }
}
