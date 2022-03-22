<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasAssets;
use App\Models\Relations\HasDocuments;
use App\Services\I18n\Contracts\Translatable;
use App\Services\I18n\Eloquent\TranslateProperties;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\OemFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Oem.
 *
 * @property string                         $id
 * @property string                         $key
 * @property string                         $name
 * @property CarbonImmutable                $created_at
 * @property CarbonImmutable                $updated_at
 * @property CarbonImmutable|null           $deleted_at
 * @property-read Collection<int, Asset>    $assets
 * @property-read Collection<int, Document> $documents
 * @method static OemFactory factory(...$parameters)
 * @method static Builder|Oem newModelQuery()
 * @method static Builder|Oem newQuery()
 * @method static Builder|Oem query()
 * @mixin Eloquent
 */
class Oem extends Model implements Translatable {
    use HasFactory;
    use TranslateProperties;
    use HasAssets;
    use HasDocuments;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'oems';

    public function getTranslatableKey(): ?string {
        return $this->key;
    }

    /**
     * @inheritDoc
     */
    protected function getTranslatableProperties(): array {
        return ['name'];
    }
}
