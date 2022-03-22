<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Models\Note;
use App\Services\Filesystem\ModelDiskFactory;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\UploadedFile;

class CreateQuoteNote {
    public function __construct(
        protected AuthManager $auth,
        protected ModelDiskFactory $disks,
        protected CurrentOrganization $organization,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return  array<string, mixed>
     */
    public function __invoke(mixed $root, array $args): array {
        return [
            'created' => $this->createNote(
                $args['input']['quote_id'],
                $args['input']['note'],
                $args['input']['pinned'] ?? false,
                $args['input']['files'] ?? [],
            ),
        ];
    }

    /**
     * @param array<UploadedFile> $files
     */
    public function createNote(string $documentId, string $content, bool $pinned = false, array $files = []): Note {
        // Create
        $note               = new Note();
        $note->user         = $this->auth->user();
        $note->document_id  = $documentId;
        $note->organization = $this->organization->get();
        $note->note         = $content;
        $note->pinned       = $pinned;
        $note->save();

        // Add files
        $note->files = $this->disks->getDisk($note)->storeToFiles($files);
        $note->save();

        // Return
        return $note;
    }
}
