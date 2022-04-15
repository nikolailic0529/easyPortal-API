<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Oem;
use App\Models\Organization;
use App\Models\QuoteRequest;
use App\Models\Type;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

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
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = QuoteRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        return [
            'id'              => $this->faker->uuid,
            'oem_id'          => static function (): Oem {
                return Oem::query()->first() ?? Oem::factory()->create();
            },
            'oem_custom'      => null,
            'organization_id' => static function (): Organization {
                return Organization::query()->first() ?? Organization::factory()->create();
            },
            'user_id'         => static function (): User {
                return User::query()->first() ?? User::factory()->create();
            },
            'customer_id'     => static function (): Customer {
                return Customer::query()->first() ?? Customer::factory()->create();
            },
            'customer_custom' => null,
            'type_id'         => static function (): Type {
                return Type::query()->first() ?? Type::factory()->create();
            },
            'type_custom'     => null,
            'message'         => $this->faker->text,
            'created_at'      => Date::now(),
            'updated_at'      => Date::now(),
            'deleted_at'      => null,
        ];
    }
}
