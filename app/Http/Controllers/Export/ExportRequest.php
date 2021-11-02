<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export;

use Illuminate\Foundation\Http\FormRequest;

class ExportRequest extends FormRequest {
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
            'root'             => 'required|string',
            'query'            => 'required|string|regex:/^query /',
            'operationName'    => 'string',
            'variables'        => 'array',
            'variables.limit'  => 'nullable|integer',
            'variables.offset' => 'nullable|integer',
            'headers'          => 'array',
            'headers.*'        => 'required|string|min:1',
        ];
    }
}
