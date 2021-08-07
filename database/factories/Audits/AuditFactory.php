<?php declare(strict_types = 1);

namespace Database\Factories\Audits;

use App\Models\Audits\Audit;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;
use stdClass;

/**
 * @method \App\Models\Audit create($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 * @method \App\Models\Audit make($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 */
class AuditFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = Audit::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        return [
            'id'          => $this->faker->uuid,
            'object_id'   => $this->faker->uuid,
            'object_type' => stdClass::class,
            'action'      => $this->faker->sentence,
            'user_id'     => null,
            'old_values'  => null,
            'new_values'  => null,
            'created_at'  => Date::now(),
            'updated_at'  => Date::now(),
        ];
    }
}
