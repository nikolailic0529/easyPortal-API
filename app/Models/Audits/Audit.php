<?php declare(strict_types = 1);

namespace App\Models\Audits;

use App\Models\Concerns\UuidAsPrimaryKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LastDragon_ru\LaraASP\Eloquent\Model;
/**
 * Audit.
 *
 * @property string                                                              $id
 * @property string                                                              $action
 * @property string|null                                                         $user_id
 * @property string                                                              $organization_id
 * @property string                                                              $object_id
 * @property string                                                              $object_type
 * @property string|null                                                         $field
 * @property string|null                                                         $old_value
 * @property string|null                                                         $new_value
 * @property \Carbon\CarbonImmutable                                             $created_at
 * @property \Carbon\CarbonImmutable                                             $updated_at
 * @method static \Database\Factories\AuditFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Audit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Audit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Audit query()
 * @mixin \Eloquent
 */
class Audit extends Model {
    use HasFactory;
    use UuidAsPrimaryKey;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $connection = 'audits';
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'audits';

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }
}
