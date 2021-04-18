<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Exports\QueryExport;
use App\Http\Requests\ExportQuery;
use Barryvdh\Snappy\Facades\SnappyPdf;
use GraphQL\Server\OperationParams;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Excel;
use Nuwave\Lighthouse\GraphQL;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function __;
use function array_key_exists;
use function array_key_first;
use function array_keys;
use function array_values;
use function fopen;
use function fputcsv;
use function is_array;

class DownloadController extends Controller {
    public function __construct(protected GraphQL $graphQL) {
        // empty
    }

    public function csv(ExportQuery $request): Response {

        $result = $this->getInitialResult($request);

        if (array_key_exists('errors', $result)) {
            return new JsonResponse($result, Response::HTTP_BAD_REQUEST);
        }

        $paginated = false;
        $data      = $result['data'];
        $root      = array_key_first($data);
        $items     = $data[$root];

        if (array_key_exists('data', $items)) {
            $paginated = true;
            $items     = $items['data'];
            if (!array_key_exists('page', $request->variables)) {
                return new JsonResponse(
                    [
                        'message' => __('validation.required', ['attribute' => 'page']),
                    ],
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }
        }

        $callback = function () use ($request, $paginated, $root, $items): void {
            $file = fopen('php://output', 'w');
            $keys = $this->getKeys($items[0]);
            // Headers of export
            fputcsv($file, array_values($keys));

            // First item which is fetched before to check for errors
            foreach ($items as $item) {
                fputcsv($file, $this->getExportRow($keys, $item));
            }

            if ($paginated) {
                $variables = $request->variables;
                $page      = $variables['page'] + 1;
                do {
                    $variables['page'] = $page;
                    $operationParam    = OperationParams::create([
                        'query'         => $request->get('query'),
                        'operationName' => $request->get('operationName'),
                        'variables'     => $variables,
                    ]);
                    $items             = $this->executeQuery($paginated, $root, $operationParam);
                    foreach ($items as $item) {
                        fputcsv($file, $this->getExportRow($keys, $item));
                    }
                    $page++;
                } while (!empty($items));
            }
        };
        $headers  = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename=export.csv',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];
        return new StreamedResponse($callback, 200, $headers);
    }

    public function excel(ExportQuery $request): Response {
        $result = $this->getInitialResult($request);

        if (array_key_exists('errors', $result)) {
            return new JsonResponse($result, Response::HTTP_BAD_REQUEST);
        }

        $paginated  = false;
        $data       = $result['data'];
        $root       = array_key_first($data);
        $items      = $data[$root];
        $collection = new Collection();
        if (array_key_exists('data', $items)) {
            $paginated = true;
            $items     = $items['data'];
            if (!array_key_exists('page', $request->variables)) {
                return new JsonResponse(
                    [
                        'message' => __('validation.required', ['attribute' => 'page']),
                    ],
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }
        }

        $keys = $this->getKeys($items[0]);

        // Header
        $collection->push(array_values($keys));

        // First item which is fetched before to check for errors
        foreach ($items as $item) {
            $collection->push($this->getExportRow($keys, $item));
        }

        if ($paginated) {
            $variables = $request->variables;
            $page      = $variables['page'] + 1;
            do {
                $variables['page'] = $page;
                $operationParam    = OperationParams::create([
                    'query'         => $request->get('query'),
                    'operationName' => $request->get('operationName'),
                    'variables'     => $variables,
                ]);
                $items             = $this->executeQuery($paginated, $root, $operationParam);
                foreach ($items as $item) {
                    $collection->push($this->getExportRow($keys, $item));
                }
                $page++;
            } while (!empty($items));
        }

        return (new QueryExport($collection))->download('export.xlsx', Excel::XLSX);
    }

    public function pdf(ExportQuery $request): Response {
        $result = $this->getInitialResult($request);

        if (array_key_exists('errors', $result)) {
            return new JsonResponse($result, Response::HTTP_BAD_REQUEST);
        }

        $paginated  = false;
        $data       = $result['data'];
        $root       = array_key_first($data);
        $items      = $data[$root];
        $collection = new Collection();
        if (array_key_exists('data', $items)) {
            $paginated = true;
            $items     = $items['data'];
            if (!array_key_exists('page', $request->variables)) {
                return new JsonResponse(
                    [
                        'message' => __('validation.required', ['attribute' => 'page']),
                    ],
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }
        }

        $keys = $this->getKeys($items[0]);

        // Header
        $collection->push(array_values($keys));

        // First item which is fetched before to check for errors
        foreach ($items as $item) {
            $collection->push($this->getExportRow($keys, $item));
        }

        if ($paginated) {
            $variables = $request->variables;
            $page      = $variables['page'] + 1;
            do {
                $variables['page'] = $page;
                $operationParam    = OperationParams::create([
                    'query'         => $request->get('query'),
                    'operationName' => $request->get('operationName'),
                    'variables'     => $variables,
                ]);
                $items             = $this->executeQuery($paginated, $root, $operationParam);
                foreach ($items as $item) {
                    $collection->push($this->getExportRow($keys, $item));
                }
                $page++;
            } while (!empty($items));
        }

        $pdf = SnappyPdf::loadView('exports.pdf', [
            'rows' => $collection,
        ]);
        return $pdf->download('export.pdf');
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
        $operationParam = OperationParams::create([
            'query'         => $request->get('query'),
            'operationName' => $request->get('operationName'),
            'variables'     => $request->get('variables'),
        ]);
        return $this->graphQL->executeOperation($operationParam);
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
