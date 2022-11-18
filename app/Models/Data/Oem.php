<?php declare(strict_types = 1);

namespace App\Models\Data;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Relations\HasAssets;
use App\Models\Relations\HasDocuments;
use App\Services\I18n\Contracts\Translatable;
use App\Services\I18n\Eloquent\TranslateProperties;
use App\Utils\Eloquent\Contracts\DataModel;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\Data\OemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Oem.
 *
 * @property string                             $id
 * @property string                             $key
 * @property string                             $name
 * @property CarbonImmutable                    $created_at
 * @property CarbonImmutable                    $updated_at
 * @property CarbonImmutable|null               $deleted_at
 * @property-read Collection<int, Asset>        $assets
 * @property-read Collection<int, Document>     $documents
 * @property-read Collection<int, ServiceGroup> $groups
 * @method static OemFactory factory(...$parameters)
 * @method static Builder<Oem> newModelQuery()
 * @method static Builder<Oem> newQuery()
 * @method static Builder<Oem> query()
 */
class Oem extends Model implements DataModel, Translatable {
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

    /**
     * @return HasMany<ServiceGroup>
     */
    public function groups(): HasMany {
        return $this->hasMany(ServiceGroup::class);
    }

    // <editor-fold desc="Translatable">
    // =========================================================================
    public function getTranslatableKey(): ?string {
        return $this->key;
    }

    /**
     * @inheritDoc
     */
    protected function getTranslatableProperties(): array {
        return ['name'];
    }
    // </editor-fold>
}
