<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\File;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Concerns\SyncMorphMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

trait HasFiles {
    use SyncMorphMany;

    #[CascadeDelete(true)]
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
