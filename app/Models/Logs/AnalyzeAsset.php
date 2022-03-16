<?php declare(strict_types = 1);

namespace App\Models\Logs;

use Carbon\CarbonImmutable;
use Database\Factories\Logs\AnalyzeAssetFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Analyze Asset.
 *
 * @property string          $id
 * @property bool|null       $unknown
 * @property bool|null       $reseller_null
 * @property string|null     $reseller_types
 * @property string|null     $reseller_unknown
 * @property bool|null       $customer_null
 * @property string|null     $customer_types
 * @property string|null     $customer_unknown
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @method static AnalyzeAssetFactory factory(...$parameters)
 * @method static Builder|AnalyzeAsset newModelQuery()
 * @method static Builder|AnalyzeAsset newQuery()
 * @method static Builder|AnalyzeAsset query()
 * @mixin Eloquent
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
