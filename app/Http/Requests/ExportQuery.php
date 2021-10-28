<?php declare(strict_types = 1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportQuery extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string,mixed>
     */
    public function rules(): array {
        return [
            'query'            => 'required|string|regex:/^query/',
            'operationName'    => 'string',
            'root'             => 'nullable|string',
            'variables'        => '',
            'variables.limit'  => 'nullable|integer',
            'variables.offset' => 'required|integer',
            'variables.order'  => '',
            'variables.where'  => '',
        ];
    }
}
