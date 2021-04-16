<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Http\Requests\ExportQuery;
use GraphQL\Server\OperationParams;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Nuwave\Lighthouse\GraphQL;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function array_key_exists;
use function array_key_first;
use function array_keys;
use function array_values;
use function fopen;
use function fputcsv;
use function is_array;

class DownloadController extends Controller {
    public function csv(
        ExportQuery $request,
        GraphQL $graphQL,
    ): Response {
        // execute first to check for errors
        $operationParam = OperationParams::create([
            'query'         => $request->get('query'),
            'operationName' => $request->get('operationName'),
            'variables'     => $request->get('variables'),
        ]);
        $result         = $graphQL->executeOperation($operationParam);
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
                        'message' => 'parameter page is required for paginated queries',
                    ],
                    Response::HTTP_BAD_REQUEST,
                );
            }
        }

        $callback = function () use ($request, $graphQL, $paginated, $root, $items): void {
            $file = fopen('php://output', 'w');
            $item = $items[0];
            $keys = [];
            /**
             * Get an array of key, values of key => path to value and value is header
             * ['products.name' => 'product]
             */
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
            // Headers of export
            fputcsv($file, array_values($keys));

            // First item which is fetched before to check for errors
            foreach ($items as $item) {
                fputcsv($file, $this->getQueryRow($keys, $item));
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
                    $result            = $graphQL->executeOperation($operationParam);
                    $items             = $result['data'][$root];

                    if ($paginated) {
                        $items = $items['data'];
                    }
                    foreach ($items as $item) {
                        fputcsv($file, $this->getQueryRow($keys, $item));
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

    /**
     * @param array<string,mixed> $keys
     *
     * @param array<string,mixed> $item
     *
     * @return array<string,mixed>
     */
    protected function getQueryRow(array $keys, array $item): array {
        $value = [];
        foreach (array_keys($keys) as $key) {
            $value[] = Arr::get($item, $key);
        }
        return $value;
    }
}
