<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetTag;
use App\Models\Data\Tag;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method AssetTag create($attributes = [], ?Model $parent = null)
 * @method AssetTag make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<AssetTag>
 */
class AssetTagFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = AssetTag::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'         => $this->faker->uuid(),
            'asset_id'   => Asset::factory(),
            'tag_id'     => Tag::factory(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
            'deleted_at' => null,
        ];
    }
}
