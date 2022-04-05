<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Document;
use App\Models\Note;
use App\Models\Organization;
use App\Models\User;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method Note create($attributes = [], ?Model $parent = null)
 * @method Note make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<Note>
 */
class NoteFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = Note::class;

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
            'document_id'     => static function (): Document {
                return Document::factory()->create();
            },
            'pinned'          => false,
            'note'            => $this->faker->text(),
            'created_at'      => Date::now(),
            'updated_at'      => Date::now(),
            'deleted_at'      => null,
        ];
    }
}
