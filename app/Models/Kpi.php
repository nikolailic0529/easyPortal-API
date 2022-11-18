<?php declare(strict_types = 1);

namespace App\Models;

use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\KpiFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Kpi.
 *
 * @property string               $id
 * @property int                  $assets_total
 * @property int                  $assets_active
 * @property float                $assets_active_percent
 * @property int                  $assets_active_on_contract
 * @property int                  $assets_active_on_warranty
 * @property int                  $assets_active_exposed
 * @property int                  $customers_active
 * @property int                  $customers_active_new
 * @property int                  $contracts_active
 * @property float                $contracts_active_amount
 * @property int                  $contracts_active_new
 * @property int                  $contracts_expiring
 * @property int                  $contracts_expired
 * @property int                  $quotes_active
 * @property float                $quotes_active_amount
 * @property int                  $quotes_active_new
 * @property int                  $quotes_expiring
 * @property int                  $quotes_expired
 * @property int                  $quotes_ordered
 * @property int                  $quotes_accepted
 * @property int                  $quotes_requested
 * @property int                  $quotes_received
 * @property int                  $quotes_rejected
 * @property int                  $quotes_awaiting
 * @property float                $service_revenue_total_amount
 * @property float                $service_revenue_total_amount_change
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @method static KpiFactory factory(...$parameters)
 * @method static Builder<Kpi> newModelQuery()
 * @method static Builder<Kpi> newQuery()
 * @method static Builder<Kpi> query()
 */
class Kpi extends Model {
    use HasFactory;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'kpis';
}
