<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\HasAssets;
use App\Models\Concerns\HasDocuments;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Oem.
 *
 * @property string                                                              $id
 * @property string                                                              $abbr
 * @property string                                                              $name
 * @property \Carbon\CarbonImmutable                                             $created_at
 * @property \Carbon\CarbonImmutable                                             $updated_at
 * @property \Carbon\CarbonImmutable|null                                        $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Asset>    $assets
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Document> $documents
 * @method static \Database\Factories\OemFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Oem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Oem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Oem query()
 * @mixin \Eloquent
 */
class Oem extends Model {
    use HasFactory;
    use HasAssets;
    use HasDocuments;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'oems';
}
