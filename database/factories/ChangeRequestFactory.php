<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\ChangeRequest;
use App\Models\Organization;
use App\Models\User;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;

use function array_keys;

/**
 * @method ChangeRequest create($attributes = [], ?Model $parent = null)
 * @method ChangeRequest make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<ChangeRequest>
 */
class ChangeRequestFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = ChangeRequest::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'              => $this->faker->uuid(),
            'organization_id' => static function (): Organization {
                return Organization::query()->first() ?? Organization::factory()->create();
            },
            'user_id'         => static function (): User {
                return User::query()->first() ?? User::factory()->create();
            },
            'object_id'       => $this->faker->uuid(),
            'object_type'     => $this->faker->randomElement(array_keys(Relation::$morphMap)),
            'subject'         => $this->faker->word(),
            'message'         => $this->faker->text(),
            'from'            => $this->faker->email(),
            'to'              => [$this->faker->email()],
            'cc'              => null,
            'bcc'             => null,
            'created_at'      => Date::now(),
            'updated_at'      => Date::now(),
            'deleted_at'      => null,
        ];
    }
}
