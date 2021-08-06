<?php declare(strict_types = 1);

namespace App\Services\Filesystem\Disks;

use App\Models\ChangeRequest;
use App\Models\File;
use App\Models\Model;
use App\Models\Note;
use App\Models\Organization;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Services\Filesystem\Disk;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Http\UploadedFile;
use LogicException;
use Symfony\Component\HttpFoundation\Response;

use function hash_file;
use function sprintf;
use function str_replace;

class ModelDisk extends Disk {
    public const ORGANIZATIONS   = 'organizations';
    public const USERS           = 'users';
    public const NOTES           = 'notes';
    public const CHANGE_REQUESTS = 'changeRequests';
    public const QUOTE_REQUESTS  = 'quoteRequests';

    /**
     * @var array<class-string<\App\Models\Model>,string>
     */
    protected array $map = [
        Organization::class  => self::ORGANIZATIONS,
        User::class          => self::USERS,
        Note::class          => self::NOTES,
        ChangeRequest::class => self::CHANGE_REQUESTS,
        QuoteRequest::class  => self::QUOTE_REQUESTS,
    ];

    public function __construct(
        Factory $factory,
        Repository $config,
        protected Model $model,
    ) {
        parent::__construct($factory, $config);
    }

    public function getName(): string {
        return $this->map[$this->getModel()::class];
    }

    public function getModel(): Model {
        return $this->model;
    }

    /**
     * @param array<string, mixed> $headers
     */
    public function download(File|string $path, string $name = null, array $headers = []): Response {
        // Possible?
        if ($path instanceof File) {
            if ($path->disk !== $this->getName()) {
                throw new LogicException(sprintf(
                    'File should be from `%s` disk, but it is from `%s` disk.',
                    $this->getName(),
                    $path->disk,
                ));
            }

            $name = $name ?: $path->name;
            $path = $path->path;
        }

        // Create
        return parent::download($path, $name, $headers);
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

    /**
     * @param array<\Illuminate\Http\UploadedFile> $uploads
     *
     * @return array<\App\Models\File>
     */
    public function storeToFiles(array $uploads): array {
        $files = [];

        foreach ($uploads as $upload) {
            $files[] = $this->storeToFile($upload);
        }

        return $files;
    }
}