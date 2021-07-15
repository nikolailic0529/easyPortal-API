<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Models\File;
use App\Models\Note;
use Illuminate\Http\UploadedFile;

use function array_key_exists;

class UpdateContractNote {
    public function __construct(
        protected CreateQuoteNote $createQuoteNote,
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
        $input      = $args['input'];
        $note       = Note::whereKey($input['id'])->first();
        $note->note = $input['note'];
        $note->save();

        $files = $note->files->keyBy(static function (File $file) {
            return $file->getKey();
        });

        if (array_key_exists('files', $input)) {
            foreach ($input['files'] as $item) {
                if (array_key_exists('content', $item) && $item['content'] instanceof UploadedFile) {
                    // new upload
                    $newFile          = $this->createQuoteNote->createFile($note, $item['content']);
                    $newFile->note_id = $note->getKey();
                    $newFile->save();
                } elseif (array_key_exists('id', $item)) {
                    // keep file
                    $files->forget($item['id']);
                } else {
                    // empty
                }
            }
        }

        foreach ($files as $file) {
            $file->delete();
        }
        return ['updated' => $note->fresh() ];
    }
}
