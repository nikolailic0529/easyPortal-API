<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Exceptions;

use Throwable;

use function trans;

class SelectorSyntaxError extends SelectorException {
    public function __construct(
        Throwable $previous = null,
    ) {
        parent::__construct('The selector is malformed.', $previous);
    }

    public function getErrorMessage(): string {
        return trans('http.controllers.export.selector_syntax_error');
    }
}
