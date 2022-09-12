<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Data\Status;
use App\Models\Document;
use App\Models\DocumentStatus;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method DocumentStatus create($attributes = [], ?Model $parent = null)
 * @method DocumentStatus make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<DocumentStatus>
 */
class DocumentStatusFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = DocumentStatus::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'          => $this->faker->uuid(),
            'document_id' => Document::factory(),
            'status_id'   => Status::factory(),
            'created_at'  => Date::now(),
            'updated_at'  => Date::now(),
            'deleted_at'  => null,
        ];
    }
}
