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
        $note                  = new Note();
        $note->user            = $this->auth->user();
        $note->document_id     = $args['input']['quote_id'];
        $note->organization_id = $this->organization->get()->getKey();
        $note->note            = $args['input']['note'];
        $note->files           = array_map(function ($file) use ($note) {
            return $this->createFile($note, $file);
        }, $args['input']['files']);
        $note->save();
        return ['created' => $note];
    }

    protected function createFile(Note $note, UploadedFile $upload): File {
        $file       = new File();
        $file->name = $upload->getClientOriginalName();
        $file->size = $upload->getSize();
        $file->type = $upload->getMimeType();
        $file->disk = $this->disk;
        $file->path = $this->store($note, $upload);
        return $file;
    }

    protected function store(Note $note, UploadedFile $file): string {
        return $file->store("{$note->getMorphClass()}/{$note->getKey()}");
    }
}
