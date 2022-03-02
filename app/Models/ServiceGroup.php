<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasDocumentEntries;
use App\Models\Relations\HasOem;
use App\Services\I18n\Contracts\Translatable;
use App\Services\I18n\Eloquent\TranslateProperties;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Service Group.
 *
 * @property string                                                              $id
 * @property string                                                              $oem_id
 * @property string                                                              $key
 * @property string                                                              $sku
 * @property string                                                              $name
 * @property \Carbon\CarbonImmutable                                             $created_at
 * @property \Carbon\CarbonImmutable                                             $updated_at
 * @property \Carbon\CarbonImmutable|null                                        $deleted_at
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\DocumentEntry> $documentEntries
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\ServiceLevel>  $levels
 * @property \App\Models\Oem                                                     $oem
 * @method static \Database\Factories\ServiceGroupFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ServiceGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ServiceGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ServiceGroup query()
 * @mixin \Eloquent
 */
class ServiceGroup extends Model implements Translatable {
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

    #[CascadeDelete(true)]
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
