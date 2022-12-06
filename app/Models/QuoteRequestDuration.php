<?php declare(strict_types = 1);

namespace App\Models;

use App\Services\I18n\Contracts\Translatable;
use App\Services\I18n\Eloquent\TranslateProperties;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\QuoteRequestDurationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * QuoteRequestDuration.
 *
 * @property string               $id
 * @property string               $key
 * @property string               $name
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @method static QuoteRequestDurationFactory factory(...$parameters)
 * @method static Builder<QuoteRequestDuration>|QuoteRequestDuration newModelQuery()
 * @method static Builder<QuoteRequestDuration>|QuoteRequestDuration newQuery()
 * @method static Builder<QuoteRequestDuration>|QuoteRequestDuration query()
 */
class QuoteRequestDuration extends Model implements Translatable {
    use HasFactory;
    use TranslateProperties;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'quote_request_durations';

    protected function getTranslatableKey(): ?string {
        return $this->key;
    }

    /**
     * @inheritdoc
     */
    protected function getTranslatableProperties(): array {
        return ['name'];
    }
}
