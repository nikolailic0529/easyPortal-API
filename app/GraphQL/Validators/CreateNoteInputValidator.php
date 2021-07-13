<?php declare(strict_types = 1);

namespace App\GraphQL\Validators;

use Illuminate\Contracts\Config\Repository;
use Nuwave\Lighthouse\Validation\Validator;

use function implode;

class CreateNoteInputValidator extends Validator {
    public function __construct(
        protected Repository $config,
    ) {
        // empty
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array {
        $maxSize   = $this->config->get('ep.file.max_size');
        $formats   = $this->config->get('ep.file.formats');
        $mimeTypes = implode(',', $formats);
        $upload    = [
            "mimes:{$mimeTypes}",
            "max:{$maxSize}",
        ];

        return [
            'note'        => ['required', 'string'],
            'document_id' => ['required', 'string'],
            'file.*'      => [...$upload],
        ];
    }
}
