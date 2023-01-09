<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Distributor;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

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
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = Distributor::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'         => $this->faker->uuid(),
            'name'       => $this->faker->company(),
            'hash'       => null,
            'changed_at' => null,
            'synced_at'  => null,
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
            'deleted_at' => null,
        ];
    }
}
