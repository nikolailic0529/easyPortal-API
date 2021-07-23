<?php declare(strict_types = 1);

namespace App\GraphQL\Validators;

use App\Rules\File;
use App\Rules\QuoteId;
use Nuwave\Lighthouse\Validation\Validator;

class CreateQuoteNoteInputValidator extends Validator {
    public function __construct(
        protected QuoteId $quoteId,
        protected File $file,
    ) {
        // empty
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array {
        return [
            'note'     => ['required', 'string'],
            'quote_id' => ['required', $this->quoteId],
            'file.*'   => [$this->file],
        ];
    }
}
