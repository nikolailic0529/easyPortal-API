<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\File;
use App\Utils\Eloquent\Concerns\SyncMorphMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

trait HasFiles {
    use SyncMorphMany;

    public function files(): MorphMany {
        return $this->morphMany(File::class, 'object');
    }

    /**
     * @param \Illuminate\Support\Collection|array<\App\Models\File> $files
     */
    public function setFilesAttribute(Collection|array $files): void {
        $this->syncMorphMany('files', $files);
    }
}
