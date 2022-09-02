<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasDocumentEntries;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\ProductGroupFactory;
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
 * @method static Builder|ProductGroup newModelQuery()
 * @method static Builder|ProductGroup newQuery()
 * @method static Builder|ProductGroup query()
 */
class ProductGroup extends Model {
    use HasFactory;
    use HasDocumentEntries;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'product_groups';
}
