<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetChangeRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

/**
 * @method \App\Models\AssetChangeRequest create($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 * @method \App\Models\AssetChangeRequest make($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 */
class AssetChangeRequestFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = AssetChangeRequest::class;

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
            'asset_id'        => static function (): Asset {
                return Asset::query()->first() ?? Asset::factory()->create();
            },
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
