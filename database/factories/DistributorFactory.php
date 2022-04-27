<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Distributor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

/**
 * @method Distributor create($attributes = [], ?Model $parent = null)
 * @method Distributor make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<Distributor>
 */
class DistributorFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = Distributor::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        return [
            'id'         => $this->faker->uuid(),
            'name'       => $this->faker->company(),
            'changed_at' => null,
            'synced_at'  => Date::now(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
            'deleted_at' => null,
        ];
    }
}
