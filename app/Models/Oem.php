<?php declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Oem.
 *
 * @property string                       $id
 * @property string                       $abbr
 * @property string                       $name
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @method static \Database\Factories\OemFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Oem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Oem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Oem query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Oem whereAbbr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Oem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Oem whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Oem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Oem whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Oem whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Oem extends Model {
    use HasFactory;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'oems';
}
