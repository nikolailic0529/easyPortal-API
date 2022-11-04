<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Rules;

use App\Http\Controllers\Export\Exceptions\SelectorException;
use App\Http\Controllers\Export\Utils\QueryOperation;
use App\Http\Controllers\Export\Utils\QueryOperationCache;
use App\Http\Controllers\Export\Utils\SelectorFactory;
use App\Utils\Validation\Traits\WithData;
use App\Utils\Validation\Traits\WithValidator;
use Exception;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\SelectionNode;
use GraphQL\Language\AST\SelectionSetNode;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\InvokableRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Str;

use function array_unique;
use function explode;
use function implode;
use function is_string;
use function trans;

class Selector implements InvokableRule, DataAwareRule, ValidatorAwareRule {
    use WithData;
    use WithValidator;

    public function __construct() {
        // empty
    }

    // <editor-fold desc="InvokableRule">
    // =========================================================================
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
            $root = SelectorFactory::make([$value]);
        } catch (SelectorException $exception) {
            $fail($exception->getErrorMessage());

            return;
        } catch (Exception $exception) {
            $fail(trans('validation.http.controllers.export.selector_invalid'));

            return;
        }

        // In query?
        if (!$this->getValidator()) {
            return;
        }

        $query = QueryOperationCache::get($this->getValidator());

        if (!$query) {
            $fail(trans('validation.http.controllers.export.query_required'));

            return;
        }

        $selectors = array_unique($root->getSelectors());
        $unknown   = [];
        $data      = (array) $this->getData();
        $root      = isset($data['root']) && is_string($data['root']) ? $data['root'] : '';
        $root      = Str::after($root, 'data.');

        foreach ($selectors as $selector) {
            if (!$this->isKnownSelector($query, $query->getOperation()->selectionSet, "{$root}.{$selector}")) {
                $unknown[] = $selector;
            }
        }

        if ($unknown) {
            $fail(trans('validation.http.controllers.export.selector_unknown', [
                'unknown' => implode(', ', $unknown),
            ]));
        }
    }
    //</editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    protected function isKnownSelector(
        QueryOperation $operation,
        SelectionSetNode|SelectionNode $node,
        string $selector,
    ): bool {
        $known         = false;
        [$name, $tail] = explode('.', $selector, 2) + ['', ''];

        if ($name === '*') {
            $known = $this->isKnownSelector($operation, $node, $tail);
        } elseif ($node instanceof SelectionSetNode) {
            foreach ($node->selections as $field) {
                if ($field instanceof FieldNode) {
                    $known = $this->isKnownSelector($operation, $field, $name);

                    if ($known && $tail && $field->selectionSet) {
                        $known = $this->isKnownSelector($operation, $field->selectionSet, $tail);
                    }
                } elseif ($field instanceof InlineFragmentNode) {
                    $known = $this->isKnownSelector($operation, $field->selectionSet, $selector);
                } elseif ($field instanceof FragmentSpreadNode) {
                    $fragment = $operation->getFragment($field->name->value);
                    $known    = $fragment && $this->isKnownSelector($operation, $fragment->selectionSet, $selector);
                } else {
                    // empty
                }

                if ($known) {
                    break;
                }
            }
        } elseif ($node instanceof FieldNode) {
            $known = ($node->alias ?? $node->name)->value === $name;
        } else {
            // empty
        }

        return $known;
    }
    //</editor-fold>
}
