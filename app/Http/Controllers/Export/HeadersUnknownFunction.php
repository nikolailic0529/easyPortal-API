<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export;

use Throwable;

use function sprintf;
use function trans;

class HeadersUnknownFunction extends ExportException {
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
        return trans('http.controllers.export.headers_unknown_function', [
            'function' => $this->function,
        ]);
    }
}
