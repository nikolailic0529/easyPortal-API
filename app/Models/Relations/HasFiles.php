<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\File;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Concerns\SyncMorphMany;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

/**
 * @mixin Model
 */
trait HasFiles {
    use SyncMorphMany;

    /**
     * @return MorphMany<File>
     */
    #[CascadeDelete]
    public function files(): MorphMany {
        return $this->morphMany(File::class, 'object');
    }

    /**
     * @param Collection<int,File>|array<File> $files
     */
    public function setFilesAttribute(Collection|array $files): void {
        $this->syncMorphMany('files', $files);
    }
}
