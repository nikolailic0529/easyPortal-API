<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export;

use App\Rules\GraphQL\Query as IsGraphQLQuery;
use App\Rules\HashMap as IsHashMap;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @phpstan-type Query array{
 *          root: string,
 *          headers: array<string,string>,
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
    public function rules(IsGraphQLQuery $isGraphQLQuery, IsHashMap $isHashMap): array {
        return [
            'root'             => 'required|string',
            'query'            => ['required', 'string', $isGraphQLQuery],
            'operationName'    => 'string',
            'variables'        => $isHashMap,
            'variables.*'      => 'nullable',
            'variables.limit'  => 'nullable|integer|min:1',
            'variables.offset' => 'nullable|integer|min:1',
            'headers'          => ['required', $isHashMap],
            'headers.*'        => 'required|string|min:1',
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
