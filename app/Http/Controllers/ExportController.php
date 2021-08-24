<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Exports\QueryExport;
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
use function array_key_exists;
use function array_key_first;
use function array_keys;
use function array_values;
use function is_array;
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
        $result = $this->getInitialResult($request, $context);

        $paginated = false;
        $data      = $result['data'];
        $validated = $request->validated();
        $root      = array_key_exists('root', $validated) && $validated['root'] ? $validated['root']
            : array_key_first($data);
        $items     = Arr::get($data, $root);
        if (!$items) {
            throw new ExportGraphQLQueryEmpty();
        }
        $collection = new Collection();
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
            do {
                $variables['page'] = $page;
                $operationParam    = OperationParams::create([
                    'query'         => $validated['query'],
                    'operationName' => $validated['operationName'],
                    'variables'     => $variables,
                ]);
                $items             = $this->executeQuery($context, $paginated, $root, $operationParam);
                foreach ($items as $item) {
                    $collection->push($this->getExportRow($keys, $item));
                }
                $page++;
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
            $value[] = Arr::get($item, $key);
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
            'variables'     => $validated['variables'],
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
                    foreach ($item[$key] as $subKey => $subValue) {
                        $keys["{$key}.{$subKey}"] = $this->formatHeader($key).' '.$this->formatHeader($subKey);
                    }
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
}
