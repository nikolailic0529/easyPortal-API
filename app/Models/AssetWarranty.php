<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\HasAsset;
use App\Models\Concerns\HasCustomer;
use App\Models\Concerns\HasDocument;
use App\Models\Concerns\HasReseller;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Asset Warranty.
 *
 * @property string                       $id
 * @property string                       $asset_id
 * @property string                       $reseller_id
 * @property string                       $customer_id
 * @property string|null                  $document_id
 * @property \Carbon\CarbonImmutable|null $start
 * @property \Carbon\CarbonImmutable      $end
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property string|null                  $note
 * @property \App\Models\Asset            $asset
 * @property \App\Models\Customer         $customer
 * @property \App\Models\Document|null    $document
 * @property \App\Models\Reseller         $reseller
 * @method static \Database\Factories\AssetWarrantyFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarranty newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarranty newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarranty query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarranty whereAssetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarranty whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarranty whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarranty whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarranty whereDocumentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarranty whereEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarranty whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarranty whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarranty whereResellerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarranty whereStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarranty whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class AssetWarranty extends Model {
    use HasFactory;
    use HasAsset;
    use HasReseller;
    use HasCustomer;
    use HasDocument;

    protected const CASTS = [
        'start' => 'date',
        'end'   => 'date',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $casts = self::CASTS + parent::CASTS;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'asset_warranties';

    public function setDocumentAttribute(Document|null $document): void {
        $this->document()->associate($document);
    }
}
