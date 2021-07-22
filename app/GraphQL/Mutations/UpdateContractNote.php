<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Models\File;
use App\Models\Note;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\UploadedFile;
use Nuwave\Lighthouse\Exceptions\AuthorizationException;

use function array_key_exists;
use function is_null;

class UpdateContractNote {
    public function __construct(
        protected CreateQuoteNote $createQuoteNote,
        protected AuthManager $auth,
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
                'updated' => $this->updateNote(
                    $args['input']['id'],
                    ['contracts-view', 'customers-view'],
                    $args['input']['note'] ?? null,
                    $args['input']['pinned'] ?? null,
                    $args['input']['files'] ?? null,
                ),
        ];
    }

    /**
     * @param array<string> $permissions
     *
     * @param array<string, mixed> $attached
     *
     */
    public function updateNote(
        string $noteId,
        array $permissions,
        string $content = null,
        bool $pinned = null,
        array $attached = null,
    ): Note {
        $note = Note::whereKey($noteId)->first();
        if (!$this->auth->user()->canAny($permissions, [$note])) {
            throw new AuthorizationException();
        }
        if ($content) {
            $note->note = $content;
        }

        if (!is_null($pinned)) {
            $note->pinned = $pinned;
        }

        if ($attached) {
            $files     = [];
            $noteFiles = $note->files->keyBy(static function (File $file) {
                return $file->getKey();
            });
            foreach ($attached as $item) {
                if (array_key_exists('content', $item) && $item['content'] instanceof UploadedFile) {
                    // new upload
                    $files [] = $this->createQuoteNote->createFile($note, $item['content']);
                } elseif (array_key_exists('id', $item)) {
                    // keep file
                    $files[] = $noteFiles->get($item['id']);
                } else {
                    // empty
                }
            }

            $note->files = $files;
        }

        $note->save();
        return $note;
    }
}
