<?php declare(strict_types = 1);

namespace App\GraphQL\Validators;

use App\Rules\Image;
use Nuwave\Lighthouse\Validation\Validator;

/**
 * @deprecated
 */
class UpdateMeProfileInputValidator extends Validator {
    public function __construct(
        protected Image $image,
    ) {
        // empty
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array {
        return [
            'given_name'     => ['nullable', 'string'],
            'family_name'    => ['nullable', 'string'],
            'office_phone'   => ['nullable', 'string'],
            'contact_email'  => ['nullable', 'email'],
            'title'          => ['nullable', 'string'],
            'academic_title' => ['nullable', 'string'],
            'department'     => ['nullable', 'string'],
            'job_title'      => ['nullable', 'string'],
            'photo'          => ['nullable', $this->image],
        ];
    }
}
