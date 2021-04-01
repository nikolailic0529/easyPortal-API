<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

/**
 * @method \App\Models\Organization create($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 * @method \App\Models\Organization make($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 */
class OrganizationFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = Organization::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        return [
            'id'                       => $this->faker->uuid,
            'subdomain'                => null,
            'name'                     => $this->faker->company,
            'locale'                   => $this->faker->locale(),
            'currency_id'              => null,
            'branding_primary_color'   => $this->faker->hexColor(),
            'branding_secondary_color' => $this->faker->hexColor(),
            'branding_logo'            => null,
            'branding_favicon'         => null,
            'branding_dark_theme'      => $this->faker->boolean(),
            'created_at'               => Date::now(),
            'updated_at'               => Date::now(),
            'deleted_at'               => null,
        ];
    }

    public function root(): static {
        // phpcs:disable SlevomatCodingStandard.Functions.StaticClosure.ClosureNotStatic
        return $this->state(function (): array {
            return [
                'subdomain' => Organization::ROOT,
            ];
        });
        // phpcs:enable
    }
}
