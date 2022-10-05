<?php declare(strict_types = 1);

namespace App\Models\Data;

use App\Models\Relations\HasAssets;
use App\Models\Relations\HasDocumentEntries;
use App\Models\Relations\HasOem;
use App\Utils\Eloquent\Contracts\DataModel;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\Data\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Product.
 *
 * @property string               $id
 * @property string               $oem_id
 * @property string               $sku
 * @property string               $name
 * @property CarbonImmutable|null $eol
 * @property CarbonImmutable|null $eos
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property Oem                  $oem
 * @method static ProductFactory factory(...$parameters)
 * @method static Builder|Product newModelQuery()
 * @method static Builder|Product newQuery()
 * @method static Builder|Product query()
 */
class Product extends Model implements DataModel {
    use HasFactory;
    use HasOem;
    use HasAssets;
    use HasDocumentEntries;

    protected const CASTS = [
        'eol' => 'date',
        'eos' => 'date',
    ] + parent::CASTS;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'products';

    /**
     * The attributes that should be cast to native types.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $casts = self::CASTS;
}
