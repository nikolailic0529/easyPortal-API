<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Rules;

use App\Http\Controllers\Export\Exceptions\SelectorException;
use App\Http\Controllers\Export\SelectorFactory;
use Exception;
use Illuminate\Contracts\Validation\InvokableRule;

use function is_string;
use function trans;

class Selector implements InvokableRule {
    /**
     * @inheritdoc
     */
    public function __invoke($attribute, $value, $fail): void {
        // Valid?
        if (!$value || !is_string($value)) {
            $fail(trans('validation.http.controllers.export.selector_required'));

            return;
        }

        // Parseable?
        try {
            SelectorFactory::make([$value]);
        } catch (SelectorException $exception) {
            $fail($exception->getErrorMessage());
        } catch (Exception $exception) {
            $fail(trans('validation.http.controllers.export.selector_invalid'));
        }
    }
}
