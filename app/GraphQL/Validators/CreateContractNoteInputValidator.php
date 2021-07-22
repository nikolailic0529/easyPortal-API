<?php declare(strict_types = 1);

namespace App\GraphQL\Validators;

use App\Rules\ContractId;
use App\Rules\File;
use Nuwave\Lighthouse\Validation\Validator;

class CreateContractNoteInputValidator extends Validator {
    public function __construct(
        protected ContractId $contractId,
        protected File $file,
    ) {
        // empty
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array {
        return [
            'note'        => ['required', 'string'],
            'contract_id' => ['required', $this->contractId],
            'file.*'      => [$this->file],
        ];
    }
}
