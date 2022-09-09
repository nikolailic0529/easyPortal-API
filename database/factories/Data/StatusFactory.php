<?php declare(strict_types = 1);

namespace Database\Factories\Data;

use App\Models\Data\Status;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;

use function array_keys;

/**
 * @method Status create($attributes = [], ?Model $parent = null)
 * @method Status make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<Status>
 */
class StatusFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = Status::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'          => $this->faker->uuid(),
            'object_type' => $this->faker->randomElement(array_keys(Relation::$morphMap)),
            'key'         => $this->faker->uuid(),
            'name'        => $this->faker->sentence(),
            'created_at'  => Date::now(),
            'updated_at'  => Date::now(),
            'deleted_at'  => null,
        ];
    }
}
