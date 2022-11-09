<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Export\Events\QueryExported;
use App\Http\Controllers\Export\Exceptions\GraphQLQueryInvalid;
use App\Http\Controllers\Export\Rows\GroupEndRow;
use App\Http\Controllers\Export\Rows\HeaderRow;
use App\Http\Controllers\Export\Rows\ValueRow;
use App\Http\Controllers\Export\Utils\Group;
use App\Http\Controllers\Export\Utils\Measurer;
use App\Http\Controllers\Export\Utils\RowsIterator;
use App\Http\Controllers\Export\Utils\SelectorFactory;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Iterators\OffsetBasedObjectIterator;
use App\Utils\Iterators\OneChunkOffsetBasedObjectIterator;
use Barryvdh\Snappy\PdfWrapper;
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
use OpenSpout\Common\Entity\Row as RowFactory;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\CSV\Writer as CSVWriter;
use OpenSpout\Writer\XLSX\OutlineRow;
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
        $format    = __FUNCTION__;
        $writer    = new CSVWriter();
        $filename  = 'export.csv';
        $mimetypes = (new MimeTypes())->getMimeTypes(pathinfo($filename, PATHINFO_EXTENSION));
        $mimetype  = reset($mimetypes);
        $headers   = [
            'Content-Type' => "{$mimetype}; charset=UTF-8",
        ];

        return $this->factory->streamDownload(
            function () use ($writer, $request, $format): void {
                $writer->openToFile('php://output');

                $iterator = $this->getRowsIterator($request, $format);

                foreach ($iterator as $row) {
                    $writer->addRow(RowFactory::fromValues($row->getColumns()));
                }

                $writer->close();
            },
            $filename,
            $headers,
        );
    }

    public function xlsx(ExportRequest $request): StreamedResponse {
        $format    = __FUNCTION__;
        $writer    = new XLSXWriter();
        $filename  = 'export.xlsx';
        $mimetypes = (new MimeTypes())->getMimeTypes(pathinfo($filename, PATHINFO_EXTENSION));
        $mimetype  = reset($mimetypes);
        $headers   = [
            'Content-Type' => "{$mimetype}; charset=UTF-8",
        ];

        return $this->factory->streamDownload(
            function () use ($writer, $request, $format): void {
                $writer->openToFile('php://output');

                $scale    = 1.1;
                $style    = (new Style())->setFontBold();
                $options  = $writer->getOptions();
                $measurer = new Measurer();
                $iterator = $this->getRowsIterator($request, $format, true);

                $options->DEFAULT_ROW_STYLE
                    ->setCellVerticalAlignment(CellVerticalAlignment::TOP);

                foreach ($iterator as $index => $row) {
                    // Add
                    $line    = null;
                    $outline = $row->getLevel() > 0
                        ? new OutlineRow(min(7, $row->getLevel()))
                        : null;

                    if ($row instanceof HeaderRow) {
                        $line = RowFactory::fromValues($row->getColumns(), $style);
                    } elseif ($row instanceof ValueRow) {
                        $line = RowFactory::fromValues($row->getColumns());
                    } else {
                        // Empty rows required to avoid outline levels merging
                        $groups = $row->getColumns();
                        $empty  = RowFactory::fromValues();

                        if ($outline) {
                            $options->setRowOutline($empty, $outline);
                        }

                        $row->setExported(count($groups));

                        foreach ($groups as $group) {
                            $writer->addRow($empty);

                            $options->mergeCells(
                                $group->getColumn(),
                                $group->getStartRow() + 1,
                                $group->getColumn(),
                                $group->getEndRow() + 1,
                            );
                        }
                    }

                    if ($line) {
                        if ($outline) {
                            $options->setRowOutline($line, $outline);
                        }

                        $writer->addRow($line);
                    }

                    // Measure
                    if ($index < 500 && ($index < 25 || $index % 25 === 0)) {
                        $measurer->measure($row->getColumns());
                    }
                }

                foreach ($measurer->getColumns() as $index => $width) {
                    assert($index >= 0, 'PHPStan false positive, seems fixed in >=1.9.0');

                    $options->setColumnWidth($width * $scale, $index + 1);
                }

                $writer->close();
            },
            $filename,
            $headers,
        );
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
            $iterator = $this->getRowsIterator($request, $format);

            foreach ($iterator as $row) {
                $rows[] = $row->getColumns();
            }

            echo $this->pdf
                ->loadView('exports.pdf', [
                    'rows' => $rows,
                ])
                ->output();
        }, $filename, $headers);
    }

    /**
     * @return ($isGroupable is true
     *      ? Iterator<array-key, ValueRow|HeaderRow|GroupEndRow>
     *      : Iterator<array-key, ValueRow|HeaderRow>)
     */
    protected function getRowsIterator(
        ExportRequest $request,
        string $format,
        bool $isGroupable = false,
    ): Iterator {
        // Prepare
        $query         = $request->validated();
        $groups        = [];
        $groupsColumns = [];
        $headerColumns = [];
        $valuesColumns = [];

        foreach (array_values($query['columns']) as $key => $column) {
            assert(
                $key >= 0,
                <<<'REASON'
                PHPStan false positive, seems fixed in >1.8.11

                https://phpstan.org/r/031dd218-f577-4ea1-96d7-05d7094543e3
                REASON,
            );

            $headerColumns[$key] = $column['name'];
            $valuesColumns[$key] = $column['value'];

            if ($isGroupable && isset($column['group']) && $column['group']) {
                $groups[$key]        = new Group($key);
                $groupsColumns[$key] = $column['group'];
            }
        }

        // Header
        $header = new HeaderRow($headerColumns);

        yield $header;

        $exported = $header->getExported();

        // Iteration
        /** @var array<int<0, max>, null> $default fixme(phpstan): why it is not detected automatically? */
        $default  = array_fill(0, count($query['columns']), null);
        $iterator = new RowsIterator(
            $this->getIterator($request, $query, $format),
            SelectorFactory::make($valuesColumns),
            SelectorFactory::make($groupsColumns),
            $groups,
            $default,
            $exported,
        );

        foreach ($iterator as $item) {
            // Item
            $exported = 0;
            $level    = $iterator->getLevel();
            $row      = new ValueRow($item, $level);

            yield $row;

            $exported += $row->getExported();

            // Groups?
            $groups = $iterator->getGroups();

            if ($groups) {
                $row = new GroupEndRow($groups, $level);

                yield $row;

                $exported += $row->getExported();
            }

            // Offset
            $iterator->setOffset($exported);
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
