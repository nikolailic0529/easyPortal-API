<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasDocumentEntries;
use App\Models\Relations\HasOem;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\ProductLineFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * ProductLine.
 *
 * @property string               $id
 * @property string               $oem_id
 * @property string               $key
 * @property string               $name
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @method static ProductLineFactory factory(...$parameters)
 * @method static Builder|ProductLine newModelQuery()
 * @method static Builder|ProductLine newQuery()
 * @method static Builder|ProductLine query()
 */
class ProductLine extends Model {
    use HasFactory;
    use HasOem;
    use HasDocumentEntries;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'product_lines';
}
