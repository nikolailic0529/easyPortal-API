<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Exceptions;

use Throwable;

use function sprintf;
use function trans;

class SelectorFunctionUnknown extends SelectorException {
    public function __construct(
        protected string $function,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf(
            'Function `%s` is unknown.',
            $this->function,
        ), $previous);
    }

    public function getErrorMessage(): string {
        return trans('http.controllers.export.selector_function_unknown', [
            'function' => $this->function,
        ]);
    }
}
