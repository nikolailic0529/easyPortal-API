<?php declare(strict_types = 1);

namespace App\Models;

use App\Utils\Eloquent\PolymorphicModel;
use Carbon\CarbonImmutable;
use Database\Factories\FileFactory;
use Eloquent;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use function app;

/**
 * File.
 *
 * @property string                 $id
 * @property string                 $object_id
 * @property string                 $object_type
 * @property string                 $name
 * @property string                 $disk
 * @property string                 $path
 * @property int                    $size
 * @property string                 $type
 * @property string                 $hash
 * @property CarbonImmutable        $created_at
 * @property CarbonImmutable        $updated_at
 * @property CarbonImmutable|null   $deleted_at
 * @property Note|QuoteRequest|null $object
 * @property-read string            $url
 * @method static FileFactory factory(...$parameters)
 * @method static Builder|File newModelQuery()
 * @method static Builder|File newQuery()
 * @method static Builder|File query()
 * @mixin Eloquent
 */
class File extends PolymorphicModel {
    use HasFactory;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'files';

    public function getUrlAttribute(): string {
        return app()->make(UrlGenerator::class)->route('files', ['id' => $this->getKey()]);
    }
}
