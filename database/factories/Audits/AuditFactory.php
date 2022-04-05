<?php declare(strict_types = 1);

namespace Database\Factories\Audits;

use App\Models\Audits\Audit;
use App\Services\Audit\Enums\Action;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;

use function array_keys;

/**
 * @method Audit create($attributes = [], ?Model $parent = null)
 * @method Audit make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<Audit>
 */
class AuditFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = Audit::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'              => $this->faker->uuid(),
            'organization_id' => null,
            'user_id'         => null,
            'object_id'       => $this->faker->uuid(),
            'object_type'     => $this->faker->randomElement(array_keys(Relation::$morphMap)),
            'action'          => $this->faker->randomElement(Action::getValues()),
            'context'         => null,
            'created_at'      => Date::now(),
            'updated_at'      => Date::now(),
        ];
    }
}
