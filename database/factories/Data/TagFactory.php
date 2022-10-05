<?php declare(strict_types = 1);

namespace Database\Factories\Data;

use App\Models\Data\Tag;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method Tag create($attributes = [], ?Model $parent = null)
 * @method Tag make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<Tag>
 */
class TagFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = Tag::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'         => $this->faker->uuid(),
            'name'       => $this->faker->name(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
            'deleted_at' => null,
        ];
    }
}
