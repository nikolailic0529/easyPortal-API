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
use function array_key_exists;
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

    public function pdf(ExportRequest $request): StreamedResponse {
        $filename  = 'export.pdf';
        $mimetypes = (new MimeTypes())->getMimeTypes(pathinfo($filename, PATHINFO_EXTENSION));
        $mimetype  = reset($mimetypes);
        $headers   = [
            'Content-Type' => "{$mimetype}; charset=UTF-8",
        ];

        return $this->factory->streamDownload(function () use ($request): void {
            $rows     = [];
            $format   = __FUNCTION__;
            $iterator = $this->getRowsIterator($request, $format, static function (array $names) use (&$rows): void {
                $rows[] = $names;
            });
            $items    = iterator_to_array($iterator);
            $rows     = array_merge($rows, $items);
            $pdf      = $this->pdf->loadView('exports.pdf', [
                'rows' => $rows,
            ]);

            echo $pdf->output();
        }, $filename, $headers);
    }

    protected function excel(
        WriterInterface $writer,
        ExportRequest $request,
        string $format,
        string $filename,
    ): StreamedResponse {
        $mimetypes      = (new MimeTypes())->getMimeTypes(pathinfo($filename, PATHINFO_EXTENSION));
        $mimetype       = reset($mimetypes);
        $headers        = [
            'Content-Type' => "{$mimetype}; charset=UTF-8",
        ];
        $headerCallback = static function (array $names) use ($writer): void {
            $style = (new Style())->setFontBold();
            $row   = Row::fromValues($names, $style);

            $writer->addRow($row);
        };
        $mergeCallback  = null;

        if ($writer instanceof XLSXWriter) {
            $mergeCallback = static function (MergedCells $merged) use ($writer): void {
                $offset = 2; // +1 for header; +1 because row coordinates are indexed from 1

                $writer->getOptions()->mergeCells(
                    $merged->getColumn(),
                    $merged->getStartRow() + $offset,
                    $merged->getColumn(),
                    $merged->getEndRow() + $offset,
                );
            };
        }

        return $this->factory->streamDownload(
            function () use ($writer, $request, $format, $headerCallback, $mergeCallback): void {
                $writer->openToFile('php://output');

                $iterator = $this->getRowsIterator($request, $format, $headerCallback, $mergeCallback);

                foreach ($iterator as $row) {
                    $writer->addRow(Row::fromValues($row));
                }

                $writer->close();
            },
            $filename,
            $headers,
        );
    }

    /**
     * @param Closure(array<string|null>): void $headerCallback
     * @param Closure(MergedCells): void|null   $mergeCallback
     *
     * @return Iterator<array<scalar|null>>
     */
    protected function getRowsIterator(
        ExportRequest $request,
        string $format,
        Closure $headerCallback,
        Closure $mergeCallback = null,
    ): Iterator {
        // Prepare
        $query         = $request->validated();
        $count         = count($query['columns']);
        $names         = array_fill(0, $count, null);
        $columns       = [];
        $mergedCells   = [];
        $mergedColumns = [];

        foreach (array_values($query['columns']) as $index => $column) {
            assert($index >= 0, <<<'REASON'
                PHPStan false positive, seems fixed in >1.8.11

                https://phpstan.org/r/031dd218-f577-4ea1-96d7-05d7094543e3
            REASON);

            $names[$index]   = $column['name'];
            $columns[$index] = $column['value'];

            if ($mergeCallback && isset($column['group']) && $column['group']) {
                $mergedCells[$index]   = new MergedCells($index);
                $mergedColumns[$index] = $column['group'];
            }
        }

        // Headers
        $headerCallback($names);

        // Iteration
        $empty          = true;
        $default        = array_fill(0, $count, null);
        $selector       = SelectorFactory::make($columns);
        $iterator       = $this->getIterator($request, $query, $format);
        $mergedSelector = SelectorFactory::make($mergedColumns);

        foreach ($iterator as $index => $item) {
            // Fill
            $row = $selector->get($item) + $default;

            // Merge
            foreach ($mergedCells as $cell) {
                $mergedValues = $mergedSelector->get($item);
                $mergedCell   = $cell->merge($index, $mergedValues[$cell->getColumn()] ?? null);

                if ($mergedCell && $mergeCallback) {
                    $mergeCallback($mergedCell);
                }
            }

            // Iterate
            yield $row;

            // Mark
            $empty = false;
        }

        // Rest
        if ($mergeCallback) {
            foreach ($mergedCells as $cell) {
                if ($cell->isMerged()) {
                    $mergeCallback($cell);
                }
            }
        }

        // Empty
        if ($empty) {
            return new EmptyIterator();
        }
    }

    /**
     * @param Query               $query
     * @param array<string,mixed> $variables
     *
     * @return array<mixed>
     */
    protected function execute(GraphQLContext $context, array $query, array $variables): array {
        $operation = $this->getOperation($query, $variables);
        $result    = $this->graphQL->executeOperation($operation, $context);
        $root      = $query['root'] ?: 'data';

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
     * @param Query $query
     *
     * @return ObjectIterator<array<string,scalar|null>>
     */
    protected function getIterator(ExportRequest $request, array $query, string $format): ObjectIterator {
        $limit           = $this->config->get('ep.export.limit');
        $chunk           = $this->config->get('ep.export.chunk');
        $context         = $this->context->generate($request);
        $executor        = function (array $variables) use ($context, $query): array {
            return $this->execute($context, $query, $variables);
        };
        $variables       = $query['variables'] ?? [];
        $iterator        = array_key_exists('offset', $variables) && array_key_exists('limit', $variables)
            ? new OffsetBasedObjectIterator($executor)
            : new OneChunkOffsetBasedObjectIterator($executor);
        $recording       = null;
        $paginationLimit = null;

        $iterator->setLimit(min($query['variables']['limit'] ?? $limit, $limit));
        $iterator->setOffset($query['variables']['offset'] ?? null);
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
        $iterator->onBeforeChunk(function () use ($query, $format): void {
            $this->dispatcher->dispatch(new QueryExported($format, $query));
        });

        return $iterator;
    }

    /**
     * @param Query               $query
     * @param array<string,mixed> $variables
     */
    protected function getOperation(array $query, array $variables): OperationParams {
        $query     = array_merge($query, [
            'variables' => array_merge($query['variables'] ?? [], $variables),
        ]);
        $operation = $this->helper->parseRequestParams('GET', [], Arr::only($query, [
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
