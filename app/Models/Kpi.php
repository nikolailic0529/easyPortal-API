<?php declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Kpi.
 *
 * @property string                       $id
 * @property string                       $object_id
 * @property string                       $object_type
 * @property int                          $assets_total
 * @property int                          $assets_active
 * @property float                        $assets_covered
 * @property int                          $customers_active
 * @property int                          $customers_active_new
 * @property int                          $contracts_active
 * @property float                        $contracts_active_amount
 * @property int                          $contracts_active_new
 * @property int                          $contracts_expiring
 * @property int                          $quotes_active
 * @property float                        $quotes_active_amount
 * @property int                          $quotes_active_new
 * @property int                          $quotes_expiring
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \App\Models\Model            $object
 * @method static \Database\Factories\KpiFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Kpi newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Kpi newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Kpi query()
 * @mixin \Eloquent
 */
class Kpi extends PolymorphicModel {
    use HasFactory;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'kpis';
}
