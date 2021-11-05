<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export;

use Throwable;

use function __;
use function sprintf;

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
        return __('export.errors.headers_unknown_function', [
            'function' => $this->function,
        ]);
    }
}
