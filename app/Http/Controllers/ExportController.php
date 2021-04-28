<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Exports\QueryExport;
use App\Http\Requests\ExportQuery;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use GraphQL\Server\OperationParams;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Excel;
use Nuwave\Lighthouse\GraphQL;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use function __;
use function array_key_exists;
use function array_key_first;
use function array_keys;
use function array_values;
use function is_array;
use function json_encode;

class ExportController extends Controller {
    public function __construct(
        protected GraphQL $graphQL,
        protected Repository $config,
    ) {
        // empty
    }

    public function csv(ExportQuery $request): BinaryFileResponse {
        $collection = $this->export($request, 'csv');
        return (new QueryExport($collection))->download('export.csv', Excel::CSV);
    }

    public function excel(ExportQuery $request): BinaryFileResponse {
        $collection = $this->export($request, 'excel');
        return (new QueryExport($collection))->download('export.xlsx', Excel::XLSX);
    }

    public function pdf(ExportQuery $request): Response {
        $collection = $this->export($request, 'pdf');
        $pdf        = PDF::loadView('exports.pdf', [
            'rows' => $collection,
        ]);
        return $pdf->download('export.pdf');
    }

    protected function export(ExportQuery $request): Collection {
        $result = $this->getInitialResult($request);

        $paginated  = false;
        $data       = $result['data'];
        $root       = array_key_first($data);
        $items      = $data[$root];
        $collection = new Collection();
        $validated  = $request->validated();
        if (array_key_exists('data', $items)) {
            $paginated = true;
            $items     = $items['data'];
            if (
                !array_key_exists('variables', $validated) ||
                !array_key_exists('page', $validated['variables'])
            ) {
                throw ValidationException::withMessages(['page' => __('validation.required', ['attribute' => 'page'])]);
            }
        }

        if (empty($items)) {
            return $collection;
        }

        $keys = $this->getKeys($items[0]);

        // Header
        $collection->push(array_values($keys));

        // First item which is fetched before to check for errors
        foreach ($items as $item) {
            $collection->push($this->getExportRow($keys, $item));
        }

        if ($paginated) {
            $variables = $validated['variables'];
            $page      = $variables['page'] + 1;
            $max       = $this->config->get('ep.export.max_records');
            $perPage   = array_key_exists('first', $variables) ? $variables['first']
                : $this->config->get('lighthouse.pagination.default_count');
            if (($perPage * $page) >= $max) {
                return $collection;
            }
            do {
                $variables['page'] = $page;
                $operationParam    = OperationParams::create([
                    'query'         => $validated['query'],
                    'operationName' => $validated['operationName'],
                    'variables'     => $variables,
                ]);
                $items             = $this->executeQuery($paginated, $root, $operationParam);
                foreach ($items as $item) {
                    $collection->push($this->getExportRow($keys, $item));
                }
                $page++;
            } while (!empty($items) && ($perPage * $page) <= $max);
        }
        return $collection;
    }

    /**
     * @param array<string,mixed> $keys
     *
     * @param array<string,mixed> $item
     *
     * @return array<string,mixed>
     */
    protected function getExportRow(array $keys, array $item): array {
        $value = [];
        foreach (array_keys($keys) as $key) {
            $value[] = Arr::get($item, $key);
        }
        return $value;
    }

    /**
     * @return array<string,mixed>
     */
    protected function executeQuery(bool $paginated, string $root, OperationParams $params): array {
        $result = $this->graphQL->executeOperation($params);
        $items  = $result['data'][$root];

        if ($paginated) {
            $items = $items['data'];
        }
        return $items;
    }

    /**
     * @return array<string,mixed>
     */
    protected function getInitialResult(ExportQuery $request): array {
        // execute first to check for errors
        $validated      = $request->validated();
        $operationParam = OperationParams::create([
            'query'         => $validated['query'],
            'operationName' => $validated['operationName'],
            'variables'     => $validated['variables'],
        ]);
        $result         = $this->graphQL->executeOperation($operationParam);
        if (array_key_exists('errors', $result)) {
            $errors = $result['errors'];
            if (
                array_key_exists('extensions', $errors[0]) &&
                array_key_exists('category', $errors[0]['extensions']) &&
                $errors[0]['extensions']['category'] === 'authentication'
            ) {
                throw new AuthorizationException($errors[0]['message']);
            }
            throw new ExportGraphQLQueryInvalid($errors);
        }
        return $result;
    }

    /**
     *  Get an array of key, values of key => path to value and value is header
     * ['products.name' => 'product]
     *
     * @param  array<string, mixed>  $item
     *
     * @return array<string,string>
     */
    protected function getKeys(array $item): array {
        $keys = [];
        foreach (array_keys($item) as $key) {
            if (is_array($item[$key])) {
                // relation key with values
                foreach ($item[$key] as $subKey => $subValue) {
                    $keys["{$key}.{$subKey}"] = "{$key}_{$subKey}";
                }
            } else {
                // Direct table column
                $keys[$key] = $key;
            }
        }
        return $keys;
    }
}
