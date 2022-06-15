<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasKpi;
use App\Utils\Eloquent\Pivot;
use App\Utils\Eloquent\SmartSave\Upsertable;
use Carbon\CarbonImmutable;
use Database\Factories\ResellerCustomerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * ResellerCustomer.
 *
 * @property string               $id
 * @property string               $reseller_id
 * @property string               $customer_id
 * @property string|null          $kpi_id
 * @property int                  $assets_count
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property Kpi|null             $kpi
 * @method static ResellerCustomerFactory factory(...$parameters)
 * @method static Builder|ResellerCustomer newModelQuery()
 * @method static Builder|ResellerCustomer newQuery()
 * @method static Builder|ResellerCustomer query()
 */
class ResellerCustomer extends Pivot implements Upsertable {
    use HasFactory;
    use HasKpi;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'reseller_customers';

    /**
     * @inheritDoc
     */
    public static function getUniqueKey(): array {
        return ['reseller_id', 'customer_id'];
    }
}
