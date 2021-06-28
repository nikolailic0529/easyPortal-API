<?php declare(strict_types = 1);

namespace App\GraphQL\Validators;

use Illuminate\Contracts\Config\Repository;
use Nuwave\Lighthouse\Validation\Validator;

use function implode;

class UpdateMeProfileInputValidator extends Validator {
    public function __construct(
        protected Repository $config,
    ) {
        // empty
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array {
        $maxSize   = $this->config->get('ep.image.max_size');
        $formats   = $this->config->get('ep.image.formats');
        $mimeTypes = implode(',', $formats);
        $upload    = [
            "mimes:{$mimeTypes}",
            "max:{$maxSize}",
        ];

        return [
            'first_name'     => ['nullable', 'string'],
            'last_name'      => ['nullable', 'string'],
            'office_phone'   => ['nullable', 'string'],
            'contact_email'  => ['nullable', 'email'],
            'title'          => ['nullable', 'string'],
            'academic_title' => ['nullable', 'string'],
            'office_phone'   => ['nullable', 'string'],
            'department'     => ['nullable', 'string'],
            'job_title'      => ['nullable', 'string'],
            'photo'          => ['nullable', ...$upload],
        ];
    }
}
