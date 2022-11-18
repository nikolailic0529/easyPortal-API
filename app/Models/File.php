<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasObject;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\FileFactory;
use Illuminate\Contracts\Mail\Attachable;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Mail\Attachment;

use function app;

/**
 * File.
 *
 * @property string                               $id
 * @property string                               $object_id
 * @property string                               $object_type
 * @property string                               $name
 * @property string                               $disk
 * @property string                               $path
 * @property int                                  $size
 * @property string                               $type
 * @property string                               $hash
 * @property CarbonImmutable                      $created_at
 * @property CarbonImmutable                      $updated_at
 * @property CarbonImmutable|null                 $deleted_at
 * @property-read string                          $url
 * @method static FileFactory factory(...$parameters)
 * @method static Builder<File> newModelQuery()
 * @method static Builder<File> newQuery()
 * @method static Builder<File> query()
 */
class File extends Model implements Attachable {
    use HasFactory;
    use HasObject;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'files';

    public function getUrlAttribute(): string {
        return app()->make(UrlGenerator::class)->route('file', ['file' => $this->getKey()]);
    }

    public function toMailAttachment(): Attachment {
        return Attachment::fromStorageDisk($this->disk, $this->path)
            ->withMime($this->type)
            ->as($this->name);
    }
}
