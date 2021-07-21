<?php declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Asset Change Request.
 *
 * @property string                       $id
 * @property string                       $subject
 * @property string                       $message
 * @property string|null                  $cc
 * @property string|null                  $bcc
 * @property string                       $organization_id
 * @property string                       $user_id
 * @property string                       $asset_id
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \App\Models\Organization     $organization
 * @property \App\Models\User             $user
 * @property \App\Models\Asset            $asset
 * @method static \Database\Factories\AssetChangeRequestFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetChangeRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetChangeRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetChangeRequest query()
 * @mixin \Eloquent
 */
class AssetChangeRequest extends Model {
    use HasFactory;

    protected const CASTS = [
        'cc'  => 'array',
        'bcc' => 'array',
    ];
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'asset_change_requests';

    /**
     * The attributes that should be cast to native types.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $casts = self::CASTS + parent::CASTS;

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function asset(): BelongsTo {
        return $this->belongsTo(Asset::class);
    }
}
