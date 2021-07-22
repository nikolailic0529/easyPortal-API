<?php declare(strict_types = 1);

namespace App\GraphQL\Validators;

use App\Rules\QuoteId;
use Illuminate\Contracts\Config\Repository;
use Nuwave\Lighthouse\Validation\Validator;

use function implode;

class CreateQuoteNoteInputValidator extends Validator {
    public function __construct(
        protected Repository $config,
        protected QuoteId $quoteId,
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
            'note'     => ['required', 'string'],
            'quote_id' => ['required', $this->quoteId],
            'file.*'   => [...$upload],
        ];
    }
}
