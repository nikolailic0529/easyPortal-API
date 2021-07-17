<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Models\File;
use App\Models\Note;
use App\Services\Filesystem\Disks\NotesDisk;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\UploadedFile;

use function array_map;

class CreateQuoteNote {
    public function __construct(
        protected AuthManager $auth,
        protected NotesDisk $disk,
        protected CurrentOrganization $organization,
    ) {
        // empty
    }
    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     *
     * @return  array<string, mixed>
     */
    public function __invoke($_, array $args): array {
        return [
            'created' => $this->createNote(
                $args['input']['quote_id'],
                $args['input']['note'],
                $args['input']['files'] ?? [],
            ),
        ];
    }

    /**
     * @param array<\Illuminate\Http\UploadedFile> $files
     */
    public function createNote(string $documentId, string $content, array $files = []): Note {
        $note                  = new Note();
        $note->user            = $this->auth->user();
        $note->document_id     = $documentId;
        $note->organization_id = $this->organization->get()->getKey();
        $note->note            = $content;
        $note->files           = array_map(function ($file) use ($note) {
            return $this->createFile($note, $file);
        }, $files);
        $note->save();
        return $note;
    }

    protected function createFile(Note $note, UploadedFile $upload): File {
        $file       = new File();
        $file->name = $upload->getClientOriginalName();
        $file->size = $upload->getSize();
        $file->type = $upload->getMimeType();
        $file->disk = $this->disk;
        $file->path = $this->disk->filesystem()->put($note->getKey(), $upload);
        $file->hash = hash_file('sha256', $this->disk->filesystem()->path($file->path));
        return $file;
    }
}
