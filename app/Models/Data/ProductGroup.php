<?php declare(strict_types = 1);

namespace App\Models\Data;

use App\Models\Relations\HasDocumentEntries;
use App\Utils\Eloquent\Contracts\DataModel;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\Data\ProductGroupFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Product Group.
 *
 * @property string               $id
 * @property string               $key
 * @property string               $name
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @method static ProductGroupFactory factory(...$parameters)
 * @method static Builder<ProductGroup>|ProductGroup newModelQuery()
 * @method static Builder<ProductGroup>|ProductGroup newQuery()
 * @method static Builder<ProductGroup>|ProductGroup query()
 */
class ProductGroup extends Model implements DataModel {
    use HasFactory;
    use HasDocumentEntries;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'product_groups';
}
