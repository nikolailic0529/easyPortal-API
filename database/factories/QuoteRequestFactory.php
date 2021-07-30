<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\Oem;
use App\Models\Organization;
use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

/**
 * @method \App\Models\QuoteRequest create($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 * @method \App\Models\QuoteRequest make($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
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
            'organization_id' => static function (): Organization {
                return Organization::query()->first() ?? Organization::factory()->create();
            },
            'user_id'         => static function (): User {
                return User::query()->first() ?? User::factory()->create();
            },
            'customer_id'     => static function (): Customer {
                return Customer::query()->first() ?? Customer::factory()->create();
            },
            'contact_id'      => static function (): Contact {
                return Contact::query()->first() ?? Contact::factory()->create();
            },
            'message'         => $this->faker->text,
            'created_at'      => Date::now(),
            'updated_at'      => Date::now(),
            'deleted_at'      => null,
        ];
    }
}
