<?php declare(strict_types = 1);

namespace App\Models\Audits;

use App\Services\Organization\Eloquent\OwnedByOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Audit.
 *
 * @property string                                                              $id
 * @property string                                                              $action
 * @property string|null                                                         $user_id
 * @property string|null                                                         $organization_id
 * @property string                                                              $object_id
 * @property string                                                              $object_type
 * @property array|null                                                          $context
 * @property \Carbon\CarbonImmutable                                             $created_at
 * @method static \Database\Factories\AuditFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Audit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Audit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Audit query()
 * @mixin \Eloquent
 */
class Audit extends Model {
    use HasFactory;
    use OwnedByOrganization;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'audits';

    protected const CASTS = [
        'context'    => 'json',
        'created_at' => 'datetime'
    ] + parent::CASTS;

    /**
     * The attributes that should be cast to native types.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $casts = self::CASTS;

    /**
     * Indicates if the model should be timestamped.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     * 
     * @var bool
     */
    public $timestamps = false;
}
