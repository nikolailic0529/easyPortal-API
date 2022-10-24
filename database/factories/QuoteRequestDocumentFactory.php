<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Document;
use App\Models\QuoteRequest;
use App\Models\QuoteRequestDocument;
use App\Models\QuoteRequestDuration;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method QuoteRequestDocument create($attributes = [], ?Model $parent = null)
 * @method QuoteRequestDocument make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<QuoteRequestDocument>
 */
class QuoteRequestDocumentFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = QuoteRequestDocument::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'          => $this->faker->uuid(),
            'request_id'  => QuoteRequest::factory(),
            'document_id' => Document::factory(),
            'duration_id' => QuoteRequestDuration::factory(),
            'created_at'  => Date::now(),
            'updated_at'  => Date::now(),
            'deleted_at'  => null,
        ];
    }
}
