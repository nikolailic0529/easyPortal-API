<?php declare(strict_types = 1);

namespace App\Models;

use App\Services\Organization\Eloquent\OwnedByOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Change Request.
 *
 * @property string                       $id
 * @property string                       $organization_id
 * @property string                       $user_id
 * @property string|null                  $asset_id
 * @property string                       $subject
 * @property string                       $from
 * @property string                       $to
 * @property string|null                  $cc
 * @property string|null                  $bcc
 * @property string                       $message
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \App\Models\Organization     $organization
 * @property \App\Models\User             $user
 * @property \App\Models\Asset            $asset
 * @method static \Database\Factories\ChangeRequestFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChangeRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChangeRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChangeRequest query()
 * @mixin \Eloquent
 */
class ChangeRequest extends Model {
    use HasFactory;
    use OwnedByOrganization;

    protected const CASTS = [
        'cc'  => 'array',
        'bcc' => 'array',
    ];
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'change_requests';

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

    public function getQualifiedOrganizationColumn(): string {
        return $this->qualifyColumn('organization_id');
    }
}
