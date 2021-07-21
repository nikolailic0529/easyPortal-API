<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\ChangeRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

/**
 * @method \App\Models\ChangeRequest create($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 * @method \App\Models\ChangeRequest make($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 */
class ChangeRequestFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = ChangeRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        return [
            'id'              => $this->faker->uuid,
            'organization_id' => static function (): Organization {
                return Organization::query()->first() ?? Organization::factory()->create();
            },
            'user_id'         => static function (): User {
                return User::query()->first() ?? User::factory()->create();
            },
            'asset_id'        => null,
            'message'         => $this->faker->text,
            'subject'         => $this->faker->word,
            'cc'              => null,
            'bcc'             => null,
            'created_at'      => Date::now(),
            'updated_at'      => Date::now(),
            'deleted_at'      => null,
        ];
    }
}
