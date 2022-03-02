<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasDocumentEntries;
use App\Models\Relations\HasOem;
use App\Models\Relations\HasServiceGroup;
use App\Services\I18n\Contracts\Translatable;
use App\Services\I18n\Eloquent\TranslateProperties;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Service Level.
 *
 * @property string                                                              $id
 * @property string                                                              $oem_id
 * @property string                                                              $service_group_id
 * @property string                                                              $key
 * @property string                                                              $sku
 * @property string                                                              $name
 * @property string                                                              $description
 * @property \Carbon\CarbonImmutable                                             $created_at
 * @property \Carbon\CarbonImmutable                                             $updated_at
 * @property \Carbon\CarbonImmutable|null                                        $deleted_at
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\DocumentEntry> $documentEntries
 * @property \App\Models\Oem                                                     $oem
 * @property \App\Models\ServiceGroup                                            $serviceGroup
 * @method static \Database\Factories\ServiceLevelFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ServiceLevel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ServiceLevel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ServiceLevel query()
 * @mixin \Eloquent
 */
class ServiceLevel extends Model implements Translatable {
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

    public function scopeTranslations(Builder $builder): Builder {
        return $builder->with('serviceGroup', static function (BelongsTo $relation): void {
            $relation->translations();
        });
    }
    // </editor-fold>
}
