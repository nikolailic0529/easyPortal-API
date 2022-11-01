<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Exceptions;

use Throwable;

use function sprintf;
use function trans;

class SelectorToManyArguments extends SelectorException {
    public function __construct(
        protected string $function,
        protected int $limit,
        protected int $count,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf(
            'Function `%s` expects at most `%s` arguments, `%s` passed.',
            $this->function,
            $this->limit,
            $this->count,
        ), $previous);
    }

    public function getErrorMessage(): string {
        return trans('http.controllers.export.selector_function_too_many_arguments', [
            'function' => $this->function,
            'limit'    => $this->limit,
            'count'    => $this->count,
        ]);
    }
}
