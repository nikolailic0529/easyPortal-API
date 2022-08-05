<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\DocumentEntry;
use App\Models\DocumentEntryField;
use App\Models\Field;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method DocumentEntryField create($attributes = [], ?Model $parent = null)
 * @method DocumentEntryField make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<DocumentEntryField>
 */
class DocumentEntryFieldFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = DocumentEntryField::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'                => $this->faker->uuid(),
            'document_entry_id' => static function (): DocumentEntry {
                return DocumentEntry::factory()->create();
            },
            'field_id'          => static function (): Field {
                return Field::factory()->create();
            },
            'value'             => $this->faker->sentence(),
            'created_at'        => Date::now(),
            'updated_at'        => Date::now(),
            'deleted_at'        => null,
        ];
    }
}
