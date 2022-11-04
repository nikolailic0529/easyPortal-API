<?php declare(strict_types = 1);

namespace App\Utils\Validation\Traits;

use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Validation\Validator;

/**
 * @see ValidatorAwareRule
 *
 * @mixin ValidatorAwareRule
 */
trait WithValidator {
    private ?Validator $validator = null;

    public function getValidator(): ?Validator {
        return $this->validator;
    }

    public function setValidator(mixed $validator): mixed {
        $this->validator = $validator;

        return $this;
    }
}
