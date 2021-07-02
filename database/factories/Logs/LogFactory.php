<?php declare(strict_types = 1);

namespace Database\Factories\Logs;

use App\Services\Logger\Models\Enums\Category;
use App\Services\Logger\Models\Enums\Status;
use App\Services\Logger\Models\Log;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

/**
 * @method \App\Services\Logger\Models\Log create($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 * @method \App\Services\Logger\Models\Log make($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 */
class LogFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = Log::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        return [
            'id'          => $this->faker->uuid,
            'category'    => $this->faker->randomElement(Category::getValues()),
            'action'      => $this->faker->word,
            'status'      => $this->faker->randomElement(Status::getValues()),
            'parent_id'   => null,
            'index'       => 0,
            'object_type' => null,
            'object_id'   => null,
            'duration'    => null,
            'context'     => null,
            'statistics'  => null,
            'created_at'  => Date::now(),
            'updated_at'  => Date::now(),
            'finished_at' => null,
        ];
    }
}