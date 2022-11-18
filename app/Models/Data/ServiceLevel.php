<?php declare(strict_types = 1);

namespace App\Models\Data;

use App\Models\DocumentEntry;
use App\Models\Relations\HasDocumentEntries;
use App\Models\Relations\HasOem;
use App\Models\Relations\HasServiceGroup;
use App\Services\I18n\Contracts\Translatable;
use App\Services\I18n\Eloquent\TranslateProperties;
use App\Utils\Eloquent\Contracts\DataModel;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\Data\ServiceLevelFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Service Level.
 *
 * @property string                         $id
 * @property string                         $oem_id
 * @property string                         $service_group_id
 * @property string                         $key
 * @property string                         $sku
 * @property string                         $name
 * @property string                         $description
 * @property CarbonImmutable                $created_at
 * @property CarbonImmutable                $updated_at
 * @property CarbonImmutable|null           $deleted_at
 * @property Collection<int, DocumentEntry> $documentEntries
 * @property Oem                            $oem
 * @property ServiceGroup                   $serviceGroup
 * @method static ServiceLevelFactory factory(...$parameters)
 * @method static Builder<ServiceLevel> newModelQuery()
 * @method static Builder<ServiceLevel> newQuery()
 * @method static Builder<ServiceLevel> query()
 */
class ServiceLevel extends Model implements DataModel, Translatable {
    use HasFactory;
    use TranslateProperties;
    use HasOem;
    use HasDocumentEntries;
    use HasServiceGroup {
        setServiceGroupAttribute as private setServiceGroupAttributeNullable;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'service_levels';

    public function setServiceGroupAttribute(ServiceGroup $group): void {
        $this->setServiceGroupAttributeNullable($group);
    }

    // <editor-fold desc="Translatable">
    // =========================================================================
    protected function getTranslatableKey(): ?string {
        return $this->key;
    }

    /**
     * @inheritDoc
     */
    protected function getTranslatableProperties(): array {
        return ['name', 'description'];
    }
    // </editor-fold>
}
