<?php declare(strict_types = 1);

namespace App\Services\Filesystem\Disks;

use App\Models\File;
use App\Models\Model;
use App\Models\Note;
use App\Services\Filesystem\Disk;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Http\UploadedFile;

use function hash_file;
use function str_replace;

class ModelDisk extends Disk {
    public const NOTES = 'notes';

    /**
     * @var array<class-string<\App\Models\Model>,string>
     */
    protected array $map = [
        Note::class => self::NOTES,
    ];

    public function __construct(
        Factory $factory,
        protected Model $model,
    ) {
        parent::__construct($factory);
    }

    public function getName(): string {
        return $this->map[$this->getModel()::class];
    }

    public function getModel(): Model {
        return $this->model;
    }

    public function store(UploadedFile $upload): string {
        $dir  = str_replace('-', '/', $this->getModel()->getKey());
        $path = $this->isPublic()
            ? $upload->storePublicly($dir, $this->getName())
            : $upload->store($dir, $this->getName());

        return $path;
    }

    public function storeToFile(UploadedFile $upload): File {
        $file              = new File();
        $file->object_id   = $this->getModel()->getKey();
        $file->object_type = $this->getModel()->getMorphClass();
        $file->name        = $upload->getClientOriginalName();
        $file->size        = $upload->getSize();
        $file->type        = $upload->getMimeType();
        $file->disk        = $this->getName();
        $file->path        = $this->store($upload);
        $file->hash        = hash_file('sha256', $upload->getPathname());

        $file->save();

        return $file;
    }
}
