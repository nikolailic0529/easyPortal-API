<?php declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

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

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'asset_change_requests';
}
