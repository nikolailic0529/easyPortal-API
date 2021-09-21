<?php declare(strict_types = 1);

namespace Database\Factories\Audits;

use App\Models\Audits\Audit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

use function array_keys;

/**
 * @method \App\Models\Audits\Audit create($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 * @method \App\Models\Audits\Audit make($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
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
            'id'              => $this->faker->uuid,
            'organization_id' => null,
            'user_id'         => null,
            'object_id'       => $this->faker->uuid,
            'object_type'     => $this->faker->randomElement(array_keys(Relation::$morphMap)),
            'action'          => $this->faker->sentence,
            'context'         => null,
            'created_at'      => Date::now(),
            'updated_at'      => Date::now(),
        ];
    }
}
