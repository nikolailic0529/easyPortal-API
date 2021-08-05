<?php declare(strict_types = 1);

namespace App\Models\Audits;

use Illuminate\Database\Eloquent\Factories\HasFactory;
/**
 * Audit.
 *
 * @property string                                                              $id
 * @property string                                                              $action
 * @property string|null                                                         $user_id
 * @property string                                                              $organization_id
 * @property string                                                              $object_id
 * @property string                                                              $object_type
 * @property string|null                                                         $old_values
 * @property string|null                                                         $new_values
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

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'audits';

    protected const CASTS = [
        'old_values' => 'json',
        'new_values' => 'json',
    ] + parent::CASTS;

    /**
     * The attributes that should be cast to native types.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $casts = self::CASTS;
}
