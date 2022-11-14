<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Export\Rules\Query as IsGraphQLQuery;
use App\Http\Controllers\Export\Rules\Selector;
use App\Rules\HashMap as IsHashMap;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @phpstan-type Query array{
 *          root: string,
 *          columns: non-empty-list<array{
 *              name: string,
 *              value: string,
 *              group?: ?string
 *          }>,
 *          query: string,
 *          operationName?: string,
 *          variables?: (array<string, mixed>&array{limit?: ?int, offset?: ?int})|null,
 *      }
 */
class ExportRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string,mixed>
     */
    public function rules(IsGraphQLQuery $isGraphQLQuery, IsHashMap $isHashMap, Selector $selector): array {
        return [
            'root'             => 'required|string',
            'query'            => ['required', 'string', $isGraphQLQuery],
            'operationName'    => 'string',
            'variables'        => $isHashMap,
            'variables.*'      => 'nullable',
            'variables.limit'  => 'nullable|integer|min:1',
            'variables.offset' => 'nullable|integer|min:1',
            'columns'          => 'required|array',
            'columns.*.name'   => 'required|string',
            'columns.*.group'  => ['string', 'distinct:strict', $selector],
            'columns.*.value'  => ['required', 'string', $selector],
        ];
    }

    /**
     * @inheritDoc
     * @return Query
     */
    public function validated($key = null, $default = null) {
        return parent::validated($key, $default);
    }
}
