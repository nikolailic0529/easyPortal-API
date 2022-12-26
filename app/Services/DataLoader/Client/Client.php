<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use App\Services\DataLoader\Client\Events\RequestFailed;
use App\Services\DataLoader\Client\Events\RequestStarted;
use App\Services\DataLoader\Client\Events\RequestSuccessful;
use App\Services\DataLoader\Client\Exceptions\DataLoaderDisabled;
use App\Services\DataLoader\Client\Exceptions\DataLoaderRequestRateTooLarge;
use App\Services\DataLoader\Client\Exceptions\DataLoaderUnavailable;
use App\Services\DataLoader\Client\Exceptions\GraphQLRequestFailed;
use App\Services\DataLoader\Client\Exceptions\GraphQLSlowQuery;
use App\Services\DataLoader\Client\GraphQL\GraphQL;
use App\Services\DataLoader\Client\GraphQL\Mutations\CoverageStatusCheck;
use App\Services\DataLoader\Client\GraphQL\Mutations\UpdateBrandingData;
use App\Services\DataLoader\Client\GraphQL\Mutations\UpdateCompanyFavicon;
use App\Services\DataLoader\Client\GraphQL\Mutations\UpdateCompanyLogo;
use App\Services\DataLoader\Client\GraphQL\Mutations\UpdateCompanyMainImageOnTheRight;
use App\Services\DataLoader\Client\GraphQL\Queries\AssetById;
use App\Services\DataLoader\Client\GraphQL\Queries\Assets;
use App\Services\DataLoader\Client\GraphQL\Queries\AssetsCount;
use App\Services\DataLoader\Client\GraphQL\Queries\AssetsCountFrom;
use App\Services\DataLoader\Client\GraphQL\Queries\CustomerAssets;
use App\Services\DataLoader\Client\GraphQL\Queries\CustomerAssetsCount;
use App\Services\DataLoader\Client\GraphQL\Queries\CustomerById;
use App\Services\DataLoader\Client\GraphQL\Queries\CustomerDocuments;
use App\Services\DataLoader\Client\GraphQL\Queries\CustomerDocumentsCount;
use App\Services\DataLoader\Client\GraphQL\Queries\Customers;
use App\Services\DataLoader\Client\GraphQL\Queries\CustomersCount;
use App\Services\DataLoader\Client\GraphQL\Queries\CustomersCountFrom;
use App\Services\DataLoader\Client\GraphQL\Queries\DistributorById;
use App\Services\DataLoader\Client\GraphQL\Queries\Distributors;
use App\Services\DataLoader\Client\GraphQL\Queries\DistributorsCount;
use App\Services\DataLoader\Client\GraphQL\Queries\DistributorsCountFrom;
use App\Services\DataLoader\Client\GraphQL\Queries\DocumentById;
use App\Services\DataLoader\Client\GraphQL\Queries\Documents;
use App\Services\DataLoader\Client\GraphQL\Queries\DocumentsCount;
use App\Services\DataLoader\Client\GraphQL\Queries\DocumentsCountFrom;
use App\Services\DataLoader\Client\GraphQL\Queries\ResellerAssets;
use App\Services\DataLoader\Client\GraphQL\Queries\ResellerAssetsCount;
use App\Services\DataLoader\Client\GraphQL\Queries\ResellerById;
use App\Services\DataLoader\Client\GraphQL\Queries\ResellerDocuments;
use App\Services\DataLoader\Client\GraphQL\Queries\ResellerDocumentsCount;
use App\Services\DataLoader\Client\GraphQL\Queries\Resellers;
use App\Services\DataLoader\Client\GraphQL\Queries\ResellersCount;
use App\Services\DataLoader\Client\GraphQL\Queries\ResellersCountFrom;
use App\Services\DataLoader\Exceptions\AssetWarrantyCheckFailed;
use App\Services\DataLoader\Exceptions\CustomerWarrantyCheckFailed;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Normalizer\Normalizers\BoolNormalizer;
use App\Services\DataLoader\Schema\Inputs\CompanyBrandingData;
use App\Services\DataLoader\Schema\Inputs\TriggerCoverageStatusCheck;
use App\Services\DataLoader\Schema\Inputs\UpdateCompanyFile;
use App\Services\DataLoader\Schema\Types\Company;
use App\Services\DataLoader\Schema\Types\Document;
use App\Services\DataLoader\Schema\Types\ViewAsset;
use App\Utils\Iterators\Contracts\ObjectIterator;
use Closure;
use DateTimeInterface;
use Exception;
use GraphQL\Type\Introspection;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use SplFileInfo;

use function array_column;
use function array_is_list;
use function assert;
use function explode;
use function is_array;
use function is_scalar;
use function is_string;
use function json_encode;
use function mb_strtolower;
use function reset;
use function str_contains;
use function time;

class Client {
    public function __construct(
        protected ExceptionHandler $handler,
        protected Dispatcher $dispatcher,
        protected Repository $config,
        protected Factory $client,
        protected Token $token,
        protected Normalizer $normalizer,
    ) {
        // empty
    }

    // <editor-fold desc="Queries">
    // =========================================================================
    public function getDistributorsCount(DateTimeInterface $from = null): int {
        return $from
            ? (int) $this->value(new DistributorsCountFrom(), [
                'from' => $this->datetime($from),
            ])
            : (int) $this->value(new DistributorsCount());
    }

    /**
     * @return ObjectIterator<Company>
     */
    public function getDistributors(
        DateTimeInterface $from = null,
        int $limit = null,
        string $lastId = null,
    ): ObjectIterator {
        return $this->getLastIdBasedIterator(
            new Distributors(),
            [
                'from' => $this->datetime($from),
            ],
            $this->getCompanyRetriever(),
            $limit,
            $lastId,
        );
    }

    public function getDistributorById(string $id): ?Company {
        return $this->get(
            new DistributorById(),
            [
                'id' => $id,
            ],
            $this->getCompanyRetriever(),
        );
    }

    public function getResellersCount(DateTimeInterface $from = null): int {
        return $from
            ? (int) $this->value(new ResellersCountFrom(), [
                'from' => $this->datetime($from),
            ])
            : (int) $this->value(new ResellersCount());
    }

    /**
     * @return ObjectIterator<Company>
     */
    public function getResellers(
        DateTimeInterface $from = null,
        int $limit = null,
        string $lastId = null,
    ): ObjectIterator {
        return $this->getLastIdBasedIterator(
            new Resellers(),
            [
                'from' => $this->datetime($from),
            ],
            $this->getCompanyRetriever(),
            $limit,
            $lastId,
        );
    }

    public function getResellerById(string $id): ?Company {
        return $this->get(
            new ResellerById(),
            [
                'id' => $id,
            ],
            $this->getCompanyRetriever(),
        );
    }

    public function getCustomersCount(DateTimeInterface $from = null): int {
        return $from
            ? (int) $this->value(new CustomersCountFrom(), [
                'from' => $this->datetime($from),
            ])
            : (int) $this->value(new CustomersCount());
    }

    /**
     * @return ObjectIterator<Company>
     */
    public function getCustomers(
        DateTimeInterface $from = null,
        int $limit = null,
        string $lastId = null,
    ): ObjectIterator {
        return $this->getLastIdBasedIterator(
            new Customers(),
            [
                'from' => $this->datetime($from),
            ],
            $this->getCompanyRetriever(),
            $limit,
            $lastId,
        );
    }

    public function getCustomerById(string $id): ?Company {
        return $this->get(
            new CustomerById(),
            [
                'id' => $id,
            ],
            $this->getCompanyRetriever(),
        );
    }

    public function runCustomerWarrantyCheck(string $id): bool {
        $error  = null;
        $input  = new TriggerCoverageStatusCheck(['customerId' => $id]);
        $result = $this->triggerCoverageStatusCheck($input, $error);

        if (!$result) {
            throw new CustomerWarrantyCheckFailed($id, $error);
        }

        return $result;
    }

    public function getAssetsCount(DateTimeInterface $from = null): int {
        return $from
            ? (int) $this->value(new AssetsCountFrom(), [
                'from' => $this->datetime($from),
            ])
            : (int) $this->value(new AssetsCount());
    }

    public function getAssetById(string $id): ?ViewAsset {
        return $this->get(
            new AssetById(),
            [
                'id' => $id,
            ],
            $this->getAssetRetriever(),
        );
    }

    public function runAssetWarrantyCheck(string $id): bool {
        $error  = null;
        $input  = new TriggerCoverageStatusCheck(['assetId' => $id]);
        $result = $this->triggerCoverageStatusCheck($input, $error);

        if (!$result) {
            throw new AssetWarrantyCheckFailed($id, $error);
        }

        return $result;
    }

    public function getAssetsByCustomerIdCount(
        string $id,
        DateTimeInterface $from = null,
    ): int {
        return (int) $this->value(new CustomerAssetsCount(), [
            'id'   => $id,
            'from' => $this->datetime($from),
        ]);
    }

    /**
     * @return ObjectIterator<ViewAsset>
     */
    public function getAssetsByCustomerId(
        string $id,
        DateTimeInterface $from = null,
        int $limit = null,
        string $lastId = null,
    ): ObjectIterator {
        return $this->getLastIdBasedIterator(
            new CustomerAssets(),
            [
                'id'   => $id,
                'from' => $this->datetime($from),
            ],
            $this->getAssetRetriever(),
            $limit,
            $lastId,
        );
    }

    public function getAssetsByResellerIdCount(
        string $id,
        DateTimeInterface $from = null,
    ): int {
        return (int) $this->value(new ResellerAssetsCount(), [
            'id'   => $id,
            'from' => $this->datetime($from),
        ]);
    }

    /**
     * @return ObjectIterator<ViewAsset>
     */
    public function getAssetsByResellerId(
        string $id,
        DateTimeInterface $from = null,
        int $limit = null,
        string $lastId = null,
    ): ObjectIterator {
        return $this->getLastIdBasedIterator(
            new ResellerAssets(),
            [
                'id'   => $id,
                'from' => $this->datetime($from),
            ],
            $this->getAssetRetriever(),
            $limit,
            $lastId,
        );
    }

    /**
     * @return ObjectIterator<ViewAsset>
     */
    public function getAssets(
        DateTimeInterface $from = null,
        int $limit = null,
        string $lastId = null,
    ): ObjectIterator {
        return $this->getLastIdBasedIterator(
            new Assets(),
            [
                'from' => $this->datetime($from),
            ],
            $this->getAssetRetriever(),
            $limit,
            $lastId,
        );
    }

    public function getDocumentsCount(DateTimeInterface $from = null): int {
        return $from
            ? (int) $this->value(new DocumentsCountFrom(), [
                'from' => $this->datetime($from),
            ])
            : (int) $this->value(new DocumentsCount());
    }

    /**
     * @return ObjectIterator<Document>
     */
    public function getDocuments(
        DateTimeInterface $from = null,
        int $limit = null,
        string $lastId = null,
    ): ObjectIterator {
        return $this->getLastIdBasedIterator(
            new Documents(),
            [
                'from' => $this->datetime($from),
            ],
            $this->getDocumentRetriever(),
            $limit,
            $lastId,
        );
    }

    public function getDocumentsByResellerCount(
        string $id,
        DateTimeInterface $from = null,
    ): int {
        return (int) $this->value(new ResellerDocumentsCount(), [
            'id'   => $id,
            'from' => $this->datetime($from),
        ]);
    }

    /**
     * @return ObjectIterator<Document>
     */
    public function getDocumentsByReseller(
        string $id,
        DateTimeInterface $from = null,
        int $limit = null,
        string $lastId = null,
    ): ObjectIterator {
        return $this->getLastIdBasedIterator(
            new ResellerDocuments(),
            [
                'id'   => $id,
                'from' => $this->datetime($from),
            ],
            $this->getDocumentRetriever(),
            $limit,
            $lastId,
        );
    }

    public function getDocumentsByCustomerCount(
        string $id,
        DateTimeInterface $from = null,
    ): int {
        return (int) $this->value(new CustomerDocumentsCount(), [
            'id'   => $id,
            'from' => $this->datetime($from),
        ]);
    }

    /**
     * @return ObjectIterator<Document>
     */
    public function getDocumentsByCustomer(
        string $id,
        DateTimeInterface $from = null,
        int $limit = null,
        string $lastId = null,
    ): ObjectIterator {
        return $this->getLastIdBasedIterator(
            new CustomerDocuments(),
            [
                'id'   => $id,
                'from' => $this->datetime($from),
            ],
            $this->getDocumentRetriever(),
            $limit,
            $lastId,
        );
    }

    public function getDocumentById(string $id): ?Document {
        return $this->get(
            new DocumentById(),
            [
                'id' => $id,
            ],
            $this->getDocumentRetriever(),
        );
    }

    /**
     * @return array<mixed>
     */
    public function getIntrospection(): array {
        return $this->call('data', Introspection::getIntrospectionQuery());
    }
    // </editor-fold>

    // <editor-fold desc="Mutations">
    // =========================================================================
    public function updateBrandingData(CompanyBrandingData $input): bool {
        return (bool) $this->value(new UpdateBrandingData(), [
            'input' => $input,
        ]);
    }

    public function updateCompanyLogo(UpdateCompanyFile $input): ?string {
        return ((string) $this->value(
            new UpdateCompanyLogo(),
            [
                'input' => $input->toArray(),
            ],
            [
                'input.file',
            ],
        )) ?: null;
    }

    public function updateCompanyFavicon(UpdateCompanyFile $input): ?string {
        return ((string) $this->value(
            new UpdateCompanyFavicon(),
            [
                'input' => $input->toArray(),
            ],
            [
                'input.file',
            ],
        )) ?: null;
    }

    public function updateCompanyMainImageOnTheRight(UpdateCompanyFile $input): ?string {
        return ((string) $this->value(
            new UpdateCompanyMainImageOnTheRight(),
            [
                'input' => $input->toArray(),
            ],
            [
                'input.file',
            ],
        )) ?: null;
    }

    protected function triggerCoverageStatusCheck(TriggerCoverageStatusCheck $input, string &$error = null): bool {
        $value  = $this->value(new CoverageStatusCheck(), [
            'input' => $input->toArray(),
        ]);
        $result = BoolNormalizer::normalize($value);
        $error  = $result !== true && $value && is_string($value)
            ? ($this->normalizer->string($value) ?: null)
            : null;

        return (bool) $result;
    }
    // </editor-fold>

    // <editor-fold desc="API">
    // =========================================================================
    public function isEnabled(): bool {
        return $this->config->get('ep.data_loader.enabled')
            && $this->config->get('ep.data_loader.url')
            && $this->config->get('ep.data_loader.client_id')
            && $this->config->get('ep.data_loader.client_secret');
    }

    /**
     * @template T
     *
     * @param array<string,mixed>      $variables
     * @param Closure(array<mixed>): T $retriever
     *
     * @return ObjectIterator<T>
     */
    public function getOffsetBasedIterator(
        GraphQL $graphql,
        array $variables,
        Closure $retriever,
        int $limit = null,
        string|int|null $offset = null,
    ): ObjectIterator {
        return (new QueryIterator(
            OffsetBasedIterator::class,
            new Query($this, $graphql, $variables),
            $retriever,
        ))
            ->setChunkSize($this->config->get('ep.data_loader.chunk'))
            ->setOffset($offset)
            ->setLimit($limit);
    }

    /**
     * @template T
     *
     * @param array<string,mixed>      $variables
     * @param Closure(array<mixed>): T $retriever
     *
     * @return ObjectIterator<T>
     */
    public function getLastIdBasedIterator(
        GraphQL $graphql,
        array $variables,
        Closure $retriever,
        int $limit = null,
        string $lastId = null,
    ): ObjectIterator {
        return (new QueryIterator(
            LastIdBasedIterator::class,
            new Query($this, $graphql, $variables),
            $retriever,
        ))
            ->setChunkSize($this->config->get('ep.data_loader.chunk'))
            ->setOffset($lastId)
            ->setLimit($limit);
    }

    /**
     * @template T of object
     *
     * @param array<mixed>             $variables
     * @param Closure(array<mixed>): T $retriever
     *
     * @return T|null
     */
    public function get(GraphQL $graphql, array $variables, Closure $retriever): ?object {
        $results = (array) $this->call("data.{$graphql->getSelector()}", (string) $graphql, $variables);
        $item    = array_is_list($results) ? (reset($results) ?: null) : $results;

        if ($item) {
            $item = $retriever($item);
        } else {
            $item = null;
        }

        return $item;
    }

    /**
     * @param array<string, mixed> $variables
     * @param array<string>        $files
     */
    public function value(
        GraphQL $graphql,
        array $variables = [],
        array $files = [],
    ): string|float|int|bool|null {
        $value = $this->call("data.{$graphql->getSelector()}", (string) $graphql, $variables, $files);

        assert(is_scalar($value) || $value === null);

        return $value;
    }

    /**
     * @param array<string, mixed> $variables
     * @param array<string>        $files
     */
    public function call(string $selector, string $graphql, array $variables = [], array $files = []): mixed {
        $json   = $this->callExecute($selector, $graphql, $variables, $files);
        $errors = Arr::get($json, 'errors', Arr::get($json, 'error.errors'));
        $result = Arr::get($json, $selector);

        assert($errors === null || is_array($errors));

        if ($errors) {
            $isRateTooLarge = (bool) Arr::first(
                array_column($errors, 'message'),
                static function (mixed $error): bool {
                    return is_string($error)
                        && str_contains(mb_strtolower($error), mb_strtolower('Request rate is large'));
                },
            );
            $error          = $isRateTooLarge
                ? new DataLoaderRequestRateTooLarge($graphql, $variables, $errors)
                : new GraphQLRequestFailed($graphql, $variables, $errors);

            $this->dispatcher->dispatch(new RequestFailed($selector, $graphql, $variables, $json));

            throw $error;
        } else {
            $this->dispatcher->dispatch(new RequestSuccessful($selector, $graphql, $variables, $json));
        }

        // Return
        return $result;
    }

    /**
     * @param array<string, mixed> $variables
     * @param array<string>        $files
     */
    protected function callExecute(string $selector, string $graphql, array $variables, array $files): mixed {
        // Enabled?
        if (!$this->isEnabled()) {
            throw new DataLoaderDisabled();
        }

        // Prepare
        $url     = $this->config->get('ep.data_loader.endpoint') ?: $this->config->get('ep.data_loader.url');
        $timeout = $this->config->get('ep.data_loader.timeout') ?: 5 * 60;
        $headers = [
            'Accept'        => 'application/json',
            'Authorization' => "Bearer {$this->token->getAccessToken()}",
        ];
        $request = $this->client->connectTimeout($timeout)->timeout($timeout)->withHeaders($headers);
        $data    = $this->callData($selector, $graphql, $variables, $files, $request, [
            'query'     => $graphql,
            'variables' => $variables,
        ]);

        // Call
        $json  = null;
        $begin = time();

        try {
            $this->dispatcher->dispatch(new RequestStarted($selector, $graphql, $variables));

            $response = $request->post($url, $data);
            $json     = $response->json();

            $response->throw();
        } catch (ConnectionException $exception) {
            $this->dispatcher->dispatch(new RequestFailed($selector, $graphql, $variables, null, $exception));

            throw new DataLoaderUnavailable($graphql, $variables, $exception);
        } catch (Exception $exception) {
            $error = new GraphQLRequestFailed($graphql, $variables, [], $exception);

            $this->dispatcher->dispatch(new RequestFailed($selector, $graphql, $variables, null, $exception));
            $this->handler->report($error);

            throw $error;
        }

        // Slow log
        $slowlog = (int) $this->config->get('ep.data_loader.slowlog', 0);
        $time    = time() - $begin;

        if ($slowlog > 0 && $time >= $slowlog) {
            $this->handler->report(new GraphQLSlowQuery($graphql, $variables, $time, $slowlog));
        }

        // Return
        return $json;
    }

    /**
     * @param array<string, mixed> $variables
     * @param array<string>        $files
     * @param array<mixed>         $data
     *
     * @return array<mixed>
     */
    protected function callData(
        string $selector,
        string $graphql,
        array $variables,
        array $files,
        PendingRequest $request,
        array $data,
    ): array {
        if ($files) {
            $map   = [];
            $index = 0;

            foreach ($files as $variable) {
                $name = 'file'.$index;
                $file = Arr::get($variables, $variable);

                if ($file) {
                    $index      = $index + 1;
                    $map[$name] = ["variables.{$variable}"];

                    if ($file instanceof SplFileInfo) {
                        $file = Utils::streamFor(Utils::tryFopen($file->getPathname(), 'r'));
                    }

                    $request->attach($name, $file);

                    Arr::set($variables, $variable, null);
                }
            }

            $data = [
                [
                    'name'     => 'operations',
                    'headers'  => ['Content-Type' => 'application/json'],
                    'contents' => json_encode([
                        'query'         => $graphql,
                        'variables'     => $variables,
                        'operationName' => Arr::last(explode('.', $selector)),
                    ]),
                ],
                [
                    'name'     => 'map',
                    'headers'  => ['Content-Type' => 'application/json'],
                    'contents' => $map ? json_encode($map) : null,
                ],
            ];
        }

        return $data;
    }

    protected function datetime(?DateTimeInterface $datetime): ?string {
        return $datetime
            ? "{$datetime->getTimestamp()}{$datetime->format('v')}"
            : null;
    }
    // </editor-fold>

    // <editor-fold desc="Retrievers">
    // =========================================================================
    /**
     * @return Closure(array<mixed>): Company
     */
    protected function getCompanyRetriever(): Closure {
        return static function (array $data): Company {
            return new Company($data);
        };
    }

    /**
     * @return Closure(array<mixed>): ViewAsset
     */
    protected function getAssetRetriever(): Closure {
        return static function (array $data): ViewAsset {
            return new ViewAsset($data);
        };
    }

    /**
     * @return Closure(array<mixed>): Document
     */
    protected function getDocumentRetriever(): Closure {
        return static function (array $data): Document {
            return new Document($data);
        };
    }
    // </editor-fold>
}
