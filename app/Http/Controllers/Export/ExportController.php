<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Utils\Iterators\ObjectIterator;
use App\Utils\Iterators\OffsetBasedObjectIterator;
use App\Utils\Iterators\OneChunkOffsetBasedObjectIterator;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\WriterInterface;
use Closure;
use EmptyIterator;
use GraphQL\Server\Helper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Iterator;
use Laravel\Telescope\Telescope;
use Nuwave\Lighthouse\GraphQL;
use Nuwave\Lighthouse\Support\Contracts\CreatesContext;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Mime\MimeTypes;

use function array_column;
use function array_filter;
use function array_is_list;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_values;
use function count;
use function explode;
use function implode;
use function is_array;
use function iterator_to_array;
use function json_encode;
use function pathinfo;
use function preg_match;
use function reset;
use function str_replace;
use function trim;

use const JSON_PRESERVE_ZERO_FRACTION;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const PATHINFO_EXTENSION;

class ExportController extends Controller {
    public function __construct(
        protected Repository $config,
        protected Dispatcher $dispatcher,
        protected ResponseFactory $factory,
        protected GraphQL $graphQL,
        protected CreatesContext $context,
        protected Helper $helper,
    ) {
        // empty
    }

    public function csv(ExportRequest $request): StreamedResponse {
        return $this->excel(WriterEntityFactory::createCSVWriter(), $request, __FUNCTION__, 'export.csv');
    }

    public function xlsx(ExportRequest $request): StreamedResponse {
        return $this->excel(WriterEntityFactory::createXLSXWriter(), $request, __FUNCTION__, 'export.xlsx');
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
        $pdf      = PDF::loadView('exports.pdf', [
            'rows' => $rows,
        ]);

        return $pdf->download('export.pdf');
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

        return $this->factory->streamDownload(function () use ($writer, $request, $format): void {
            $writer   = $writer->openToFile('php://output');
            $headers  = static function (array $headers) use ($writer): void {
                $style = (new StyleBuilder())->setFontBold()->build();
                $row   = WriterEntityFactory::createRowFromArray($headers, $style);

                $writer->addRow($row);
            };
            $iterator = $this->getRowsIterator($request, $format, $headers);

            foreach ($iterator as $row) {
                $writer->addRow(WriterEntityFactory::createRowFromArray($row));
            }

            $writer->close();
        }, $filename, $headers);
    }

    /**
     * @param \Closure(array<string>): void $headersCallback
     *
     * @return \Iterator<array<mixed>>
     */
    protected function getRowsIterator(ExportRequest $request, string $format, Closure $headersCallback): Iterator {
        // Prepare request
        $parameters = $request->validated();
        $iterator   = $this->getIterator($request, $parameters);
        $headers    = isset($parameters['headers']) && is_array($parameters['headers'])
            ? $parameters['headers']
            : null;
        $empty      = true;
        $root       = $parameters['root'] ?? 'data';

        foreach ($iterator as $i => $item) {
            // If no headers we need to calculate them. But this is deprecated
            // behavior and probably we should remove it. The main problem is
            // we are using only the first row and if some values are missed
            // the headers will be invalid.
            if (!$headers) {
                $headers = $this->getHeaders($item);
            }

            // If no headers we cannot export because it will get empty row.
            if (!$headers) {
                break;
            }

            // Add Headers
            if ($i === 0) {
                $headersCallback(array_values($headers));
                $this->event($format, $root, $headers);
            }

            // Prepare row
            $row = [];

            foreach ($headers as $header => $title) {
                $row[] = $this->getHeaderValue($header, $item);
            }

            // Iterate
            yield $row;

            // Mark
            $empty = false;
        }

        // Empty
        if ($empty) {
            // Add headers
            if ($headers) {
                $headersCallback(array_values($headers));
            }

            // Event
            $this->event($format, $root, $headers);

            // Return
            return new EmptyIterator();
        }
    }

    /**
     * @param array<string,mixed> $parameters
     * @param array<string,mixed> $variables
     *
     * @return array<mixed>
     */
    protected function execute(GraphQLContext $context, array $parameters, array $variables): array {
        $parameters = array_merge($parameters, [
            'variables' => array_merge($parameters['variables'] ?? [], $variables),
        ]);
        $operation  = $this->helper->parseRequestParams('GET', [], Arr::only($parameters, [
            'query',
            'variables',
            'operationName',
        ]));
        $result     = $this->graphQL->executeOperation($operation, $context);
        $root       = $parameters['root'] ?? 'data';

        if (isset($result['errors'])) {
            switch ($result['errors'][0]['extensions']['category'] ?? null) {
                case 'authorization':
                    throw new AuthorizationException($result['errors'][0]['message']);
                    break;
                case 'authentication':
                    throw new AuthenticationException($result['errors'][0]['message']);
                    break;
                default:
                    throw new GraphQLQueryInvalid($result['errors']);
                    break;
            }
        }

        return Arr::get($result, $root) ?? [];
    }

    /**
     * @param array<string,mixed> $parameters
     *
     * @return \App\Utils\Iterators\ObjectIterator<array<string,mixed>>
     */
    protected function getIterator(ExportRequest $request, array $parameters): ObjectIterator {
        $chunk           = $this->getChunkSize();
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

        $iterator->setLimit($parameters['variables']['limit'] ?? null);
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

            if ($paginationLimit < $chunk) {
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

        return $iterator;
    }

    /**
     * @param array<string, mixed> $item
     *
     * @return array<string, string>
     */
    protected function getHeaders(array $item, string $prefix = null): array {
        $headers = [];

        if (!array_is_list($item)) {
            foreach ($item as $name => $value) {
                $key = (string) ($prefix ? "{$prefix}.{$name}" : $name);

                if (is_array($value) && !array_is_list($value)) {
                    $headers = array_merge($headers, $this->getHeaders($value, $key));
                } else {
                    $headers[$key] = $this->getHeader($key);
                }
            }
        }

        return $headers;
    }

    protected function getHeader(string $path): string {
        return Str::title(trim(
            str_replace(['_', '.'], ' ', $path),
        ));
    }

    /**
     * @param array<mixed> $item
     */
    protected function getHeaderValue(string $header, array $item): mixed {
        $value = null;

        if (preg_match('/^(?<function>[\w]+)\((?<arguments>[^)]+)\)$/', $header, $matches)) {
            $function  = $matches['function'];
            $arguments = array_map(
                fn(string $arg): mixed => $this->getItemValue(trim($arg), $item),
                explode(',', $matches['arguments']),
            );

            switch ($function) {
                case 'concat':
                    $value = trim(implode(' ', array_filter($arguments)));
                    break;
                default:
                    throw new HeadersUnknownFunction($function);
                    break;
            }
        } else {
            $value = $this->getItemValue($header, $item);
        }

        return $value;
    }

    /**
     * @param array<mixed> $item
     */
    protected function getItemValue(string $path, array $item): mixed {
        // We don't use `data_get()` here to simplify headers: you can use the
        // dot-separated string in all cases and you no need to worry about
        // lists (which is not visible in GraphQL query).
        $parts = explode('.', $path);
        $value = $item;

        foreach ($parts as $part) {
            if (is_array($value)) {
                if (array_is_list($value)) {
                    $value = array_column($value, $part);
                } else {
                    $value = Arr::get($value, $part);
                }
            } else {
                $value = $value[$part] ?? null;
            }

            if (!$value) {
                break;
            }
        }

        // Return
        return $this->getValue($value);
    }

    protected function getValue(mixed $value): mixed {
        if (is_array($value)) {
            $flags = JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
            $first = reset($value);

            if (is_array($first)) {
                if (array_is_list($value) && !Arr::first($value, static fn($v) => count($v) > 1)) {
                    $value = implode(', ', array_map(static fn($v) => reset($v), $value));
                } else {
                    $value = json_encode($value, $flags);
                }
            } elseif (array_is_list($value)) {
                $value = implode(', ', $value);
            } else {
                $value = json_encode($value, $flags);
            }
        }

        return $value;
    }

    /**
     * @param array<string,string> $headers
     */
    protected function event(string $format, string $root, ?array $headers): void {
        $this->dispatcher->dispatch(new QueryExported(
            $format,
            $root,
            $headers,
        ));
    }

    protected function getChunkSize(): ?int {
        return $this->config->get('ep.export.chunk') ?: 500;
    }
}
