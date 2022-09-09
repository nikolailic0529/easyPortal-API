<?php declare(strict_types = 1);

namespace App\Models\Data;

use App\Models\DocumentEntry;
use App\Models\Relations\HasDocumentEntries;
use App\Models\Relations\HasOem;
use App\Services\I18n\Contracts\Translatable;
use App\Services\I18n\Eloquent\TranslateProperties;
use App\Utils\Eloquent\Contracts\DataModel;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\Data\ServiceGroupFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Service Group.
 *
 * @property string                         $id
 * @property string                         $oem_id
 * @property string                         $key
 * @property string                         $sku
 * @property string                         $name
 * @property CarbonImmutable                $created_at
 * @property CarbonImmutable                $updated_at
 * @property CarbonImmutable|null           $deleted_at
 * @property Collection<int, DocumentEntry> $documentEntries
 * @property Collection<int, ServiceLevel>  $levels
 * @property Oem                            $oem
 * @method static ServiceGroupFactory factory(...$parameters)
 * @method static Builder|ServiceGroup newModelQuery()
 * @method static Builder|ServiceGroup newQuery()
 * @method static Builder|ServiceGroup query()
 */
class ServiceGroup extends Model implements DataModel, Translatable {
    use HasFactory;
    use TranslateProperties;
    use HasOem;
    use HasDocumentEntries;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'service_groups';

    /**
     * @return HasMany<ServiceLevel>
     */
    public function levels(): HasMany {
        return $this->hasMany(ServiceLevel::class);
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
