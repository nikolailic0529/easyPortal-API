<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Data\Status;
use App\Models\Reseller;
use App\Models\ResellerStatus;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method ResellerStatus create($attributes = [], ?Model $parent = null)
 * @method ResellerStatus make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<ResellerStatus>
 */
class ResellerStatusFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = ResellerStatus::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'          => $this->faker->uuid(),
            'reseller_id' => Reseller::factory(),
            'status_id'   => Status::factory(),
            'created_at'  => Date::now(),
            'updated_at'  => Date::now(),
            'deleted_at'  => null,
        ];
    }
}
