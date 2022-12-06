<?php declare(strict_types = 1);

namespace App\Models\Data;

use App\Models\Relations\HasDocumentEntries;
use App\Utils\Eloquent\Contracts\DataModel;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\Data\ProductLineFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * ProductLine.
 *
 * @property string               $id
 * @property string               $key
 * @property string               $name
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @method static ProductLineFactory factory(...$parameters)
 * @method static Builder<ProductLine>|ProductLine newModelQuery()
 * @method static Builder<ProductLine>|ProductLine newQuery()
 * @method static Builder<ProductLine>|ProductLine query()
 */
class ProductLine extends Model implements DataModel {
    use HasFactory;
    use HasDocumentEntries;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'product_lines';
}
