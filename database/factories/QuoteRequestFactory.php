<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Data\Oem;
use App\Models\Data\Type;
use App\Models\Organization;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method QuoteRequest create($attributes = [], ?Model $parent = null)
 * @method QuoteRequest make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<QuoteRequest>
 */
class QuoteRequestFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = QuoteRequest::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'              => $this->faker->uuid(),
            'oem_id'          => Oem::factory(),
            'oem_custom'      => null,
            'organization_id' => Organization::factory(),
            'user_id'         => User::factory(),
            'customer_id'     => Customer::factory(),
            'customer_custom' => null,
            'type_id'         => Type::factory(),
            'type_custom'     => null,
            'message'         => $this->faker->text(),
            'created_at'      => Date::now(),
            'updated_at'      => Date::now(),
            'deleted_at'      => null,
        ];
    }
}
