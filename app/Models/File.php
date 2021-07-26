<?php declare(strict_types = 1);

namespace App\Models;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;

use function app;

/**
 * File.
 *
 * @property string                       $id
 * @property string                       $object_id
 * @property string                       $object_type
 * @property string                       $name
 * @property string                       $disk
 * @property string                       $path
 * @property int                          $size
 * @property string                       $type
 * @property string                       $hash
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \App\Models\Note             $note
 * @property-read string                  $url
 * @method static \Database\Factories\FileFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\File newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\File newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\File query()
 * @mixin \Eloquent
 */
class File extends PolymorphicModel {
    use HasFactory;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'files';

    public function note(): MorphTo {
        return $this->morphTo(Note::class, 'object_type', 'object_id');
    }

    public function getUrlAttribute(): string {
        return app()->make(UrlGenerator::class)->route('files', ['id' => $this->getKey()]);
    }
}
