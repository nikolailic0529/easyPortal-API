<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\HasAssets;
use App\Models\Concerns\HasDocuments;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * File.
 *
 * @property string                                                              $id
 * @property string                                                              $name
 * @property string                                                              $disk
 * @property string                                                              $path
 * @property int                                                                 $size
 * @property string                                                              $type
 * @property \Carbon\CarbonImmutable                                             $created_at
 * @property \Carbon\CarbonImmutable                                             $updated_at
 * @property \Carbon\CarbonImmutable|null                                        $deleted_at
 * @method static \Database\Factories\FileFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\File newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\File newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\File query()
 * @mixin \Eloquent
 */
class File extends Model {
    use HasFactory;
    use HasAssets;
    use HasDocuments;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'files';
}
