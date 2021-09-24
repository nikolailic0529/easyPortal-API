<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\Relations\HasAsset;
use App\Models\Concerns\Relations\HasCustomerNullable;
use App\Models\Concerns\Relations\HasDocument;
use App\Models\Concerns\Relations\HasResellerNullable;
use App\Models\Concerns\Relations\HasServiceGroup;
use App\Models\Concerns\SyncBelongsToMany;
use App\Services\Organization\Eloquent\OwnedByReseller;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

/**
 * Asset Warranty.
 *
 * @property string                                                             $id
 * @property string                                                             $asset_id
 * @property string|null                                                        $reseller_id
 * @property string|null                                                        $customer_id
 * @property string|null                                                        $document_id
 * @property string|null                                                        $document_number
 * @property string|null                                                        $service_group_id
 * @property \Carbon\CarbonImmutable|null                                       $start
 * @property \Carbon\CarbonImmutable|null                                       $end
 * @property \Carbon\CarbonImmutable                                            $created_at
 * @property \Carbon\CarbonImmutable                                            $updated_at
 * @property \Carbon\CarbonImmutable|null                                       $deleted_at
 * @property string|null                                                        $note
 * @property \App\Models\Asset                                                  $asset
 * @property \App\Models\Customer|null                                          $customer
 * @property \App\Models\Document|null                                          $document
 * @property \App\Models\Reseller|null                                          $reseller
 * @property \App\Models\ServiceGroup|null                                      $serviceGroup
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\ServiceLevel> $serviceLevels
 * @method static \Database\Factories\AssetWarrantyFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarranty newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarranty newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarranty query()
 * @mixin \Eloquent
 */
class AssetWarranty extends Model {
    use OwnedByReseller;
    use HasFactory;
    use HasAsset;
    use HasServiceGroup;
    use HasResellerNullable;
    use HasCustomerNullable;
    use HasDocument;
    use SyncBelongsToMany;

    protected const CASTS = [
        'start' => 'date',
        'end'   => 'date',
    ] + parent::CASTS;

    /**
     * The attributes that should be cast to native types.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $casts = self::CASTS;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'asset_warranties';

    public function setDocumentAttribute(Document|null $document): void {
        $this->document()->associate($document);
    }

    public function serviceLevels(): BelongsToMany {
        $pivot = new AssetWarrantyServiceLevel();

        return $this
            ->belongsToMany(ServiceLevel::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }

    /**
     * @param \Illuminate\Support\Collection<\App\Models\ServiceLevel>|array<\App\Models\ServiceLevel> $levels
     */
    public function setServiceLevelsAttribute(Collection|array $levels): void {
        $this->syncBelongsToMany('serviceLevels', $levels);
    }
}
