<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Language;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method Language create($attributes = [], ?Model $parent = null)
 * @method Language make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<Language>
 */
class LanguageFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = Language::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'         => $this->faker->uuid(),
            'code'       => $this->faker->languageCode(),
            'name'       => $this->faker->languageCode(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
            'deleted_at' => null,
        ];
    }
}
