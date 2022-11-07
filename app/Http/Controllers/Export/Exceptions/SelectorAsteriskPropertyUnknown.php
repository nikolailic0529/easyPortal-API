<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Exceptions;

use Throwable;

use function trans;

class SelectorAsteriskPropertyUnknown extends SelectorException {
    public function __construct(
        Throwable $previous = null,
    ) {
        parent::__construct('The asterisk selector must have a property selector.', $previous);
    }

    public function getErrorMessage(): string {
        return trans('http.controllers.export.selector_asterisk_property_unknown');
    }
}
