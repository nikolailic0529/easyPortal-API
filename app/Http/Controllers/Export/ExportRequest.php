<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export;

use App\Rules\GraphQL\Query as IsGraphQLQuery;
use App\Rules\HashMap;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @phpstan-type Query array{
 *          root: string,
 *          query: string,
 *          operationName?: string,
 *          variables?: (array<string, mixed>&array{limit?: ?int, offset?: ?int})|null,
 *          headers?: array<string,string>,
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
    public function rules(IsGraphQLQuery $query): array {
        return [
            'root'             => 'required|string',
            'query'            => ['required', 'string', $query],
            'operationName'    => 'string',
            'variables'        => new HashMap(),
            'variables.*'      => 'nullable',
            'variables.limit'  => 'nullable|integer|min:1',
            'variables.offset' => 'nullable|integer|min:1',
            'headers'          => new HashMap(),
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
