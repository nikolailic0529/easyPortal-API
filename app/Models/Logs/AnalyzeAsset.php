<?php declare(strict_types = 1);

namespace App\Models\Logs;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Analyze Asset.
 *
 * @property string                  $id
 * @property bool|null               $unknown
 * @property bool|null               $reseller_null
 * @property string|null             $reseller_types
 * @property string|null             $reseller_unknown
 * @property bool|null               $customer_null
 * @property string|null             $customer_types
 * @property string|null             $customer_unknown
 * @property \Carbon\CarbonImmutable $created_at
 * @property \Carbon\CarbonImmutable $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Logs\AnalyzeAsset newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Logs\AnalyzeAsset newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Logs\AnalyzeAsset query()
 * @mixin \Eloquent
 */
class AnalyzeAsset extends Model {
    use HasFactory;

    /**
     * The attributes that should be cast to native types.
     */
    protected const CASTS = [
        'unknown'       => 'bool',
        'reseller_null' => 'bool',
        'customer_null' => 'bool',
    ] + parent::CASTS;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'analyze_assets';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $casts = self::CASTS;
}
