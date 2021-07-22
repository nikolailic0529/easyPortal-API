<?php declare(strict_types = 1);

namespace App\GraphQL\Validators;

use App\Rules\File;
use App\Rules\FileId;
use App\Rules\NoteId;
use Nuwave\Lighthouse\Validation\Validator;

class UpdateContractNoteInputValidator extends Validator {
    public function __construct(
        protected NoteId $noteId,
        protected FileId $fileId,
        protected File $file,
    ) {
        // empty
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array {
        return [
            'note'           => ['nullable', 'string'],
            'pinned'         => ['nullable', 'boolean'],
            'id'             => ['required', $this->noteId],
            'file.*.content' => ['nullable', $this->file],
            'file.*.id'      => ['nullable', $this->fileId],
        ];
    }
}
