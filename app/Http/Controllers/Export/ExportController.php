<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Export\Exceptions\GraphQLQueryInvalid;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Iterators\OffsetBasedObjectIterator;
use App\Utils\Iterators\OneChunkOffsetBasedObjectIterator;
use Barryvdh\Snappy\PdfWrapper;
use Closure;
use EmptyIterator;
use GraphQL\Server\Helper;
use GraphQL\Server\OperationParams;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Iterator;
use Laravel\Telescope\Telescope;
use Nuwave\Lighthouse\GraphQL;
use Nuwave\Lighthouse\Support\Contracts\CreatesContext;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\CSV\Writer as CSVWriter;
use OpenSpout\Writer\WriterInterface;
use OpenSpout\Writer\XLSX\Writer as XLSXWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Mime\MimeTypes;

use function array_fill;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_values;
use function assert;
use function count;
use function is_array;
use function iterator_to_array;
use function min;
use function pathinfo;
use function reset;

use const PATHINFO_EXTENSION;

/**
 * @phpstan-import-type Query from ExportRequest
 */
class ExportController extends Controller {
    public function __construct(
        protected Repository $config,
        protected Dispatcher $dispatcher,
        protected ResponseFactory $factory,
        protected GraphQL $graphQL,
        protected CreatesContext $context,
        protected Helper $helper,
        protected PdfWrapper $pdf,
    ) {
        // empty
    }

    public function csv(ExportRequest $request): StreamedResponse {
        return $this->excel(new CSVWriter(), $request, __FUNCTION__, 'export.csv');
    }

    public function xlsx(ExportRequest $request): StreamedResponse {
        return $this->excel(new XLSXWriter(), $request, __FUNCTION__, 'export.xlsx');
    }

    public function pdf(ExportRequest $request): Response {
        $rows     = [];
        $format   = __FUNCTION__;
        $headers  = static function (array $headers) use (&$rows): void {
            $rows[] = $headers;
        };
        $iterator = $this->getRowsIterator($request, $format, $headers);
        $items    = iterator_to_array($iterator);
        $rows     = array_merge($rows, $items);
        $pdf      = $this->pdf
            ->loadView('exports.pdf', [
                'rows' => $rows,
            ])
            ->download('export.pdf');

        return $pdf;
    }

    protected function excel(
        WriterInterface $writer,
        ExportRequest $request,
        string $format,
        string $filename,
    ): StreamedResponse {
        $mimetypes = (new MimeTypes())->getMimeTypes(pathinfo($filename, PATHINFO_EXTENSION));
        $mimetype  = reset($mimetypes);
        $headers   = [
            'Content-Type' => "{$mimetype}; charset=UTF-8",
        ];

        $writer->openToFile('php://output');

        return $this->factory->streamDownload(function () use ($writer, $request, $format): void {
            $headers  = static function (array $headers) use ($writer): void {
                $style = (new Style())->setFontBold();
                $row   = Row::fromValues($headers, $style);

                $writer->addRow($row);
            };
            $iterator = $this->getRowsIterator($request, $format, $headers);

            foreach ($iterator as $row) {
                $writer->addRow(Row::fromValues($row));
            }

            $writer->close();
        }, $filename, $headers);
    }

    /**
     * @param Closure(array<string>): void $headersCallback
     *
     * @return Iterator<array<scalar|null>>
     */
    protected function getRowsIterator(ExportRequest $request, string $format, Closure $headersCallback): Iterator {
        // Headers
        $parameters = $request->validated();
        $headers    = $this->getHeaders($parameters);

        if ($headers) {
            $headersCallback($headers);
        }

        // Iteration
        $empty    = true;
        $count    = count($parameters['headers']);
        $columns  = array_keys($parameters['headers']);
        $selector = SelectorFactory::make($columns);
        $iterator = $this->getIterator($request, $parameters, $format);

        foreach ($iterator as $item) {
            // Fill
            $row = array_fill(0, $count, null);

            $selector->fill($item, $row);

            // Iterate
            yield $row;

            // Mark
            $empty = false;
        }

        // Empty
        if ($empty) {
            return new EmptyIterator();
        }
    }

    /**
     * @param Query               $parameters
     * @param array<string,mixed> $variables
     *
     * @return array<mixed>
     */
    protected function execute(GraphQLContext $context, array $parameters, array $variables): array {
        $operation = $this->getOperation($parameters, $variables);
        $result    = $this->graphQL->executeOperation($operation, $context);
        $root      = $parameters['root'] ?: 'data';

        if (isset($result['errors']) && is_array($result['errors'])) {
            switch ($result['errors'][0]['extensions']['category'] ?? null) {
                case 'authorization':
                    throw new AuthorizationException($result['errors'][0]['message']);
                case 'authentication':
                    throw new AuthenticationException($result['errors'][0]['message']);
                default:
                    throw new GraphQLQueryInvalid($operation, $result['errors']);
            }
        }

        $result = (array) Arr::get($result, $root);

        return $result;
    }

    /**
     * @param Query $parameters
     *
     * @return ObjectIterator<array<string,scalar|null>>
     */
    protected function getIterator(ExportRequest $request, array $parameters, string $format): ObjectIterator {
        $limit           = $this->config->get('ep.export.limit');
        $chunk           = $this->config->get('ep.export.chunk');
        $context         = $this->context->generate($request);
        $executor        = function (array $variables) use ($context, $parameters): array {
            return $this->execute($context, $parameters, $variables);
        };
        $variables       = $parameters['variables'] ?? [];
        $iterator        = array_key_exists('offset', $variables) && array_key_exists('limit', $variables)
            ? new OffsetBasedObjectIterator($executor)
            : new OneChunkOffsetBasedObjectIterator($executor);
        $recording       = null;
        $paginationLimit = null;

        $iterator->setLimit(min($parameters['variables']['limit'] ?? $limit, $limit));
        $iterator->setOffset($parameters['variables']['offset'] ?? null);
        $iterator->setChunkSize($chunk);
        $iterator->onInit(function () use (&$recording, &$paginationLimit, $chunk): void {
            // Telescope should be disabled because it stored all data in memory
            // and will dump it only after the job/command/request is finished.
            // For long-running requests, this will lead to huge memory usage
            $recording = Telescope::isRecording();

            if ($recording) {
                Telescope::stopRecording();
            }

            // We need to override pagination limit to speed up export.
            $paginationLimit = $this->config->get('ep.pagination.limit.max');

            if ($paginationLimit < $chunk || $chunk === null) {
                $this->config->set('ep.pagination.limit.max', $chunk);
            }
        });
        $iterator->onFinish(function () use ($recording, $paginationLimit): void {
            // Restore previous state
            if ($recording) {
                Telescope::startRecording();
            }

            if ($paginationLimit) {
                $this->config->set('ep.pagination.limit.max', $paginationLimit);
            }
        });
        $iterator->onBeforeChunk(function () use ($parameters, $format): void {
            $this->dispatcher->dispatch(new QueryExported(
                $format,
                $parameters['root'],
                $parameters['headers'],
            ));
        });

        return $iterator;
    }

    /**
     * @param Query $parameters
     *
     * @return non-empty-array<string>|null
     */
    protected function getHeaders(array $parameters): ?array {
        $headers = array_values($parameters['headers']);
        $headers = array_filter($headers) ? $headers : null;

        return $headers;
    }

    /**
     * @param Query               $parameters
     * @param array<string,mixed> $variables
     */
    protected function getOperation(array $parameters, array $variables): OperationParams {
        $parameters = array_merge($parameters, [
            'variables' => array_merge($parameters['variables'] ?? [], $variables),
        ]);
        $operation  = $this->helper->parseRequestParams('GET', [], Arr::only($parameters, [
            'query',
            'variables',
            'operationName',
        ]));

        if (is_array($operation)) {
            $operation = reset($operation);
        }

        assert($operation instanceof OperationParams);

        return $operation;
    }
}
