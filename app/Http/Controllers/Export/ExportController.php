<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export;

use App\Exports\QueryExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExportQuery;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use GraphQL\Server\OperationParams;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Excel;
use Nuwave\Lighthouse\GraphQL;
use Nuwave\Lighthouse\Support\Contracts\CreatesContext;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use function __;
use function array_column;
use function array_key_exists;
use function array_key_first;
use function array_keys;
use function array_merge;
use function array_values;
use function count;
use function implode;
use function is_array;
use function is_scalar;
use function json_encode;
use function str_replace;
use function ucwords;

class ExportController extends Controller {
    public function __construct(
        protected GraphQL $graphQL,
        protected Dispatcher $dispatcher,
    ) {
        // empty
    }

    public function csv(ExportQuery $request, CreatesContext $createsContext): BinaryFileResponse {
        $collection = $this->export($request, $createsContext->generate($request));
        $this->dispatchExported($collection, $request, 'csv');

        return (new QueryExport($collection))->download('export.csv', Excel::CSV);
    }

    public function excel(ExportQuery $request, CreatesContext $createsContext): BinaryFileResponse {
        $collection = $this->export($request, $createsContext->generate($request));
        $this->dispatchExported($collection, $request, 'xlsx');

        return (new QueryExport($collection))->download('export.xlsx', Excel::XLSX);
    }

    public function pdf(ExportQuery $request, CreatesContext $createsContext): Response {
        $collection = $this->export($request, $createsContext->generate($request));
        $pdf        = PDF::loadView('exports.pdf', [
            'rows' => $collection,
        ]);
        $this->dispatchExported($collection, $request, 'pdf');

        return $pdf->download('export.pdf');
    }

    protected function export(ExportQuery $request, GraphQLContext $context): Collection {
        $result    = $this->getInitialResult($request, $context);
        $paginated = false;
        $data      = $result['data'];
        $validated = $request->validated();
        $root      = array_key_exists('root', $validated) && $validated['root'] ? $validated['root']
            : array_key_first($data);
        $items     = Arr::get($data, $root);
        $count     = $validate['variables']['limit'] ?? null;
        if (!$items) {
        // TODO Remove? throw new ExportGraphQLQueryEmpty();
        }
        $collection = new Collection();
        if (array_key_exists('data', $items)) {
            $paginated = true;
            $items     = $items['data'];
            if (
                !array_key_exists('variables', $validated) ||
                !array_key_exists('offset', $validated['variables'])
            ) {
                throw ValidationException::withMessages([
                    'offset' => __('validation.required', ['attribute' => 'offset']),
                ]);
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

            if ($count !== null && $count >= count($collection)) {
                break;
            }
        }

        if ($paginated && ($count === null || $count < count($collection))) {
            $variables = $validated['variables'];
            $offset    = count($collection);
            $limit     = 100;

            do {
                $variables['offset'] = $offset;
                $variables['limit']  = $limit;
                $operationParam      = OperationParams::create([
                    'query'         => $validated['query'],
                    'operationName' => $validated['operationName'],
                    'variables'     => $variables,
                ]);
                $items               = $this->executeQuery($context, $paginated, $root, $operationParam);
                $offset              = $offset + $limit;

                foreach ($items as $item) {
                    $collection->push($this->getExportRow($keys, $item));

                    if ($count !== null && $count >= count($collection)) {
                        break;
                    }
                }
            } while (!empty($items));
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
            $current = Arr::get($item, $key);
            if (is_array($current)) {
                // TODO Format array output
                // Check if object of array has only (1) key
                if (Arr::isAssoc($current[0]) && count(array_keys($current[0])) === 1) {
                    $current = implode(',', array_column($current, array_key_first($current[0])));
                } else {
                    $current = json_encode($current);
                }
            }
            $value[] = $current;
        }

        return $value;
    }

    /**
     * @return array<string,mixed>
     */
    protected function executeQuery(
        GraphQLContext $context,
        bool $paginated,
        string $root,
        OperationParams $params,
    ): array {
        $result = $this->graphQL->executeOperation($params, $context);
        $items  = $result['data'][$root];

        if ($paginated) {
            $items = $items['data'];
        }

        return $items;
    }

    /**
     * @return array<string,mixed>
     */
    protected function getInitialResult(ExportQuery $request, GraphQLContext $context): array {
        // execute first to check for errors
        $validated      = $request->validated();
        $operationParam = OperationParams::create([
            'query'         => $validated['query'],
            'operationName' => $validated['operationName'],
            'variables'     => array_merge($validated['variables'], [
                'limit' => 100,
            ]),
        ]);
        $result         = $this->graphQL->executeOperation($operationParam, $context);
        if (array_key_exists('errors', $result)) {
            switch ($result['errors'][0]['extensions']['category'] ?? null) {
                case 'authorization':
                    throw new AuthorizationException($result['errors'][0]['message']);
                    break;
                case 'authentication':
                    throw new AuthenticationException($result['errors'][0]['message']);
                    break;
                default:
                    throw new ExportGraphQLQueryInvalid($result['errors']);
                    break;
            }
        }

        return $result;
    }

    /**
     *  Get an array of key, values of key => path to value and value is header
     * ['products.name' => 'product]
     *
     * @param array<string, mixed> $item
     *
     * @return array<string,string>
     */
    protected function getKeys(array $item): array {
        $keys = [];
        foreach (array_keys($item) as $key) {
            if (is_array($item[$key])) {
                // relation key with values
                if (Arr::isAssoc($item[$key])) {
                    $this->resolveObjectHeader($item, $keys, $key);
                } else {
                    // Array of values
                    $keys[$key] = $this->formatHeader($key);
                }
            } else {
                // Direct table column
                $keys[$key] = $this->formatHeader($key);
            }
        }

        return $keys;
    }

    protected function formatHeader(string $text): string {
        $text = str_replace('.', ' ', $text);
        $text = str_replace('_', ' ', $text);

        return ucwords($text);
    }

    protected function dispatchExported(Collection $collection, ExportQuery $request, string $type): void {
        $count     = $collection->count();
        $count     = $count > 1 ? $count - 1 : $count;
        $validated = $request->validated();
        $this->dispatcher->dispatch(new QueryExported(
            $count,
            $type,
            $validated['operationName'],
            $collection->first(),
        ));
    }

    /**
     * @param array<string, mixed> $array
     *
     * @param array<string, mixed> $keys
     */
    protected function resolveObjectHeader(array $array, array &$keys, string $key): void {
        $currentValue = Arr::get($array, $key);
        if (is_scalar($currentValue)) {
            $keys[$key] = $this->formatHeader($key);
        } elseif (Arr::isAssoc($currentValue)) {
            foreach ($currentValue as $index => $value) {
                $prefix = "{$key}.{$index}";
                $this->resolveObjectHeader($array, $keys, $prefix);
            }
        } else {
            // empty
        }
    }
}
