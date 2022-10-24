<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Models\Note;
use App\Services\Filesystem\ModelDiskFactory;
use App\Utils\Eloquent\Callbacks\GetKey;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Nuwave\Lighthouse\Exceptions\AuthorizationException;

use function is_null;

class UpdateContractNote {
    public function __construct(
        protected Gate $gate,
        protected ModelDiskFactory $disks,
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
            'updated' => $this->updateNote(
                $args['input']['id'],
                ['contracts-view'],
                $args['input']['note'] ?? null,
                $args['input']['pinned'] ?? null,
                $args['input']['files'] ?? null,
            ),
        ];
    }

    /**
     * @param array<string>                                         $permissions
     * @param array<array{id: string}|array{content: UploadedFile}> $attached
     */
    public function updateNote(
        string $noteId,
        array $permissions,
        string $content = null,
        bool $pinned = null,
        array $attached = null,
    ): Note {
        $note = Note::query()->whereKey($noteId)->first();

        if (!$note || $note->note === null || !$this->gate->any($permissions, [$note])) {
            throw new AuthorizationException();
        }

        if ($content) {
            $note->note = $content;
        }

        if (!is_null($pinned)) {
            $note->pinned = $pinned;
        }

        if (!is_null($attached)) {
            $disk     = $this->disks->getDisk($note);
            $files    = [];
            $existing = $note->files->keyBy(new GetKey());

            foreach ($attached as $item) {
                if (isset($item['content'])) {
                    $files [] = $disk->storeToFile($item['content']);
                } elseif (isset($item['id'])) {
                    $files[] = $existing->get($item['id']);
                } else {
                    // empty
                }
            }

            $note->files = Collection::make($files)->filter();
        }

        $note->save();

        return $note;
    }
}
