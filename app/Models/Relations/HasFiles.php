<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\File;
use App\Utils\Eloquent\Concerns\SyncMorphMany;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @mixin Model
 */
trait HasFiles {
    use SyncMorphMany;

    /**
     * @return MorphMany<File>
     */
    public function files(): MorphMany {
        return $this->morphMany(File::class, 'object');
    }

    /**
     * @param Collection<int,File> $files
     */
    public function setFilesAttribute(Collection $files): void {
        $this->syncMorphMany('files', $files);
    }
}
