<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasDocumentEntries;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\PspFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Psp.
 *
 * @property string               $id
 * @property string               $key
 * @property string               $name
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @method static PspFactory factory(...$parameters)
 * @method static Builder|Psp newModelQuery()
 * @method static Builder|Psp newQuery()
 * @method static Builder|Psp query()
 */
class Psp extends Model {
    use HasFactory;
    use HasDocumentEntries;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'psps';
}
