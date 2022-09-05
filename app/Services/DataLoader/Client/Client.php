<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use App\Services\DataLoader\Client\Events\RequestFailed;
use App\Services\DataLoader\Client\Events\RequestStarted;
use App\Services\DataLoader\Client\Events\RequestSuccessful;
use App\Services\DataLoader\Client\Exceptions\DataLoaderDisabled;
use App\Services\DataLoader\Client\Exceptions\DataLoaderUnavailable;
use App\Services\DataLoader\Client\Exceptions\GraphQLRequestFailed;
use App\Services\DataLoader\Client\Exceptions\GraphQLSlowQuery;
use App\Services\DataLoader\Exceptions\AssetWarrantyCheckFailed;
use App\Services\DataLoader\Exceptions\CustomerWarrantyCheckFailed;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\CompanyBrandingData;
use App\Services\DataLoader\Schema\Document;
use App\Services\DataLoader\Schema\TriggerCoverageStatusCheck;
use App\Services\DataLoader\Schema\UpdateCompanyFile;
use App\Services\DataLoader\Schema\ViewAsset;
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

use function array_is_list;
use function assert;
use function explode;
use function implode;
use function is_scalar;
use function json_encode;
use function reset;
use function sha1;
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
            ? (int) $this->value(
                'data.getDistributorCount',
                /** @lang GraphQL */ <<<'GRAPHQL'
                query value($from: String) {
                    getDistributorCount(fromTimestamp: $from)
                }
                GRAPHQL,
                [
                    'from' => $this->datetime($from),
                ],
            )
            : (int) $this->value(
                'data.getCentralAssetDbStatistics.companiesDistributorAmount',
                /** @lang GraphQL */ <<<'GRAPHQL'
                query {
                    getCentralAssetDbStatistics {
                        companiesDistributorAmount
                    }
                }
                GRAPHQL,
            );
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
            'getDistributors',
            /** @lang GraphQL */ <<<GRAPHQL
            query items(\$limit: Int, \$lastId: String, \$from: String) {
                getDistributors(limit: \$limit, lastId: \$lastId, fromTimestamp: \$from) {
                    {$this->getDistributorPropertiesGraphQL()}
                }
            }
            GRAPHQL,
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
            'getCompanyById',
            /** @lang GraphQL */ <<<GRAPHQL
            query getCompanyById(\$id: String!) {
                getCompanyById(id: \$id) {
                    {$this->getDistributorPropertiesGraphQL()}
                }
            }
            GRAPHQL,
            [
                'id' => $id,
            ],
            $this->getCompanyRetriever(),
        );
    }

    public function getResellersCount(DateTimeInterface $from = null): int {
        return $from
            ? (int) $this->value(
                'data.getResellerCount',
                /** @lang GraphQL */ <<<'GRAPHQL'
                query value($from: String) {
                    getResellerCount(fromTimestamp: $from)
                }
                GRAPHQL,
                [
                    'from' => $this->datetime($from),
                ],
            )
            : (int) $this->value(
                'data.getCentralAssetDbStatistics.companiesResellerAmount',
                /** @lang GraphQL */ <<<'GRAPHQL'
                query {
                    getCentralAssetDbStatistics {
                        companiesResellerAmount
                    }
                }
                GRAPHQL,
            );
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
            'getResellers',
            /** @lang GraphQL */ <<<GRAPHQL
            query items(\$limit: Int, \$lastId: String, \$from: String) {
                getResellers(limit: \$limit, lastId: \$lastId, fromTimestamp: \$from) {
                    {$this->getResellerPropertiesGraphQL()}
                }
            }
            GRAPHQL,
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
            'getCompanyById',
            /** @lang GraphQL */ <<<GRAPHQL
            query getCompanyById(\$id: String!) {
                getCompanyById(id: \$id) {
                    {$this->getResellerPropertiesGraphQL()}
                }
            }
            GRAPHQL,
            [
                'id' => $id,
            ],
            $this->getCompanyRetriever(),
        );
    }

    public function getCustomersCount(DateTimeInterface $from = null): int {
        return $from
            ? (int) $this->value(
                'data.getCustomerCount',
                /** @lang GraphQL */ <<<'GRAPHQL'
                query value($from: String) {
                    getCustomerCount(fromTimestamp: $from)
                }
                GRAPHQL,
                [
                    'from' => $this->datetime($from),
                ],
            )
            : (int) $this->value(
                'data.getCentralAssetDbStatistics.companiesCustomerAmount',
                /** @lang GraphQL */ <<<'GRAPHQL'
                query {
                    getCentralAssetDbStatistics {
                        companiesCustomerAmount
                    }
                }
                GRAPHQL,
            );
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
            'getCustomers',
            /** @lang GraphQL */ <<<GRAPHQL
            query items(\$limit: Int, \$lastId: String, \$from: String) {
                getCustomers(limit: \$limit, lastId: \$lastId, fromTimestamp: \$from) {
                    {$this->getCustomerPropertiesGraphQL()}
                }
            }
            GRAPHQL,
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
            'getCompanyById',
            /** @lang GraphQL */ <<<GRAPHQL
            query getCompanyById(\$id: String!) {
                getCompanyById(id: \$id) {
                    {$this->getCustomerPropertiesGraphQL()}
                }
            }
            GRAPHQL,
            [
                'id' => $id,
            ],
            $this->getCompanyRetriever(),
        );
    }

    public function runCustomerWarrantyCheck(string $id): bool {
        $input  = new TriggerCoverageStatusCheck(['customerId' => $id]);
        $result = $this->triggerCoverageStatusCheck($input);

        if (!$result) {
            throw new CustomerWarrantyCheckFailed($id);
        }

        return $result;
    }

    public function getAssetsCount(DateTimeInterface $from = null): int {
        return $from
            ? (int) $this->value(
                'data.getAssetCount',
                /** @lang GraphQL */ <<<'GRAPHQL'
                query value($from: String) {
                    getAssetCount(fromTimestamp: $from)
                }
                GRAPHQL,
                [
                    'from' => $this->datetime($from),
                ],
            )
            : (int) $this->value(
                'data.getCentralAssetDbStatistics.assetsAmount',
                /** @lang GraphQL */ <<<'GRAPHQL'
                query {
                    getCentralAssetDbStatistics {
                        assetsAmount
                    }
                }
                GRAPHQL,
            );
    }

    public function getAssetById(string $id): ?ViewAsset {
        return $this->get(
            'getAssets',
            /** @lang GraphQL */ <<<GRAPHQL
            query getAssets(\$id: String!) {
                getAssets(args: [{key: "id", value: \$id}]) {
                    {$this->getAssetPropertiesGraphQL()}
                }
            }
            GRAPHQL,
            [
                'id' => $id,
            ],
            $this->getAssetRetriever(),
        );
    }

    public function getAssetByIdWithDocuments(string $id): ?ViewAsset {
        return $this->get(
            'getAssets',
            /** @lang GraphQL */ <<<GRAPHQL
            query getAssets(\$id: String!) {
                getAssets(args: [{key: "id", value: \$id}]) {
                    {$this->getAssetPropertiesGraphQL()}
                    {$this->getAssetDocumentsPropertiesGraphQL()}
                }
            }
            GRAPHQL,
            [
                'id' => $id,
            ],
            $this->getAssetRetriever(),
        );
    }

    public function runAssetWarrantyCheck(string $id): bool {
        $input  = new TriggerCoverageStatusCheck(['assetId' => $id]);
        $result = $this->triggerCoverageStatusCheck($input);

        if (!$result) {
            throw new AssetWarrantyCheckFailed($id);
        }

        return $result;
    }

    public function getAssetsByCustomerIdCount(
        string $id,
        DateTimeInterface $from = null,
    ): int {
        return (int) $this->value(
            'data.getAssetsByCustomerIdCount',
            /** @lang GraphQL */ <<<'GRAPHQL'
            query value($id: String!, $from: String) {
                getAssetsByCustomerIdCount(customerId: $id, fromTimestamp: $from)
            }
            GRAPHQL,
            [
                'id'   => $id,
                'from' => $this->datetime($from),
            ],
        );
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
            'getAssetsByCustomerId',
            /** @lang GraphQL */ <<<GRAPHQL
            query items(\$id: String!, \$limit: Int, \$lastId: String, \$from: String) {
                getAssetsByCustomerId(customerId: \$id, limit: \$limit, lastId: \$lastId, fromTimestamp: \$from) {
                    {$this->getAssetPropertiesGraphQL()}
                }
            }
            GRAPHQL,
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
    public function getAssetsByCustomerIdWithDocuments(
        string $id,
        DateTimeInterface $from = null,
        int $limit = null,
        string $lastId = null,
    ): ObjectIterator {
        return $this->getLastIdBasedIterator(
            'getAssetsByCustomerId',
            /** @lang GraphQL */ <<<GRAPHQL
            query items(\$id: String!, \$limit: Int, \$lastId: String, \$from: String) {
                getAssetsByCustomerId(customerId: \$id, limit: \$limit, lastId: \$lastId, fromTimestamp: \$from) {
                    {$this->getAssetPropertiesGraphQL()}
                    {$this->getAssetDocumentsPropertiesGraphQL()}
                }
            }
            GRAPHQL,
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
        return (int) $this->value(
            'data.getAssetsByResellerIdCount',
            /** @lang GraphQL */ <<<'GRAPHQL'
            query value($id: String!, $from: String) {
                getAssetsByResellerIdCount(resellerId: $id, fromTimestamp: $from)
            }
            GRAPHQL,
            [
                'id'   => $id,
                'from' => $this->datetime($from),
            ],
        );
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
            'getAssetsByResellerId',
            /** @lang GraphQL */ <<<GRAPHQL
            query items(\$id: String!, \$limit: Int, \$lastId: String, \$from: String) {
                getAssetsByResellerId(resellerId: \$id, limit: \$limit, lastId: \$lastId, fromTimestamp: \$from) {
                    {$this->getAssetPropertiesGraphQL()}
                }
            }
            GRAPHQL,
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
    public function getAssetsByResellerIdWithDocuments(
        string $id,
        DateTimeInterface $from = null,
        int $limit = null,
        string $lastId = null,
    ): ObjectIterator {
        return $this->getLastIdBasedIterator(
            'getAssetsByResellerId',
            /** @lang GraphQL */ <<<GRAPHQL
            query items(\$id: String!, \$limit: Int, \$lastId: String, \$from: String) {
                getAssetsByResellerId(resellerId: \$id, limit: \$limit, lastId: \$lastId, fromTimestamp: \$from) {
                    {$this->getAssetPropertiesGraphQL()}
                    {$this->getAssetDocumentsPropertiesGraphQL()}
                }
            }
            GRAPHQL,
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
            'getAssets',
            /** @lang GraphQL */ <<<GRAPHQL
            query items(\$limit: Int, \$lastId: String, \$from: String) {
                getAssets(limit: \$limit, lastId: \$lastId, fromTimestamp: \$from) {
                    {$this->getAssetPropertiesGraphQL()}
                }
            }
            GRAPHQL,
            [
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
    public function getAssetsWithDocuments(
        DateTimeInterface $from = null,
        int $limit = null,
        string $lastId = null,
    ): ObjectIterator {
        return $this->getLastIdBasedIterator(
            'getAssets',
            /** @lang GraphQL */ <<<GRAPHQL
            query items(\$limit: Int, \$lastId: String, \$from: String) {
                getAssets(limit: \$limit, lastId: \$lastId, fromTimestamp: \$from) {
                    {$this->getAssetPropertiesGraphQL()}
                    {$this->getAssetDocumentsPropertiesGraphQL()}
                }
            }
            GRAPHQL,
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
            ? (int) $this->value(
                'data.getDocumentCount',
                /** @lang GraphQL */ <<<'GRAPHQL'
                query value($from: String) {
                    getDocumentCount(fromTimestamp: $from)
                }
                GRAPHQL,
                [
                    'from' => $this->datetime($from),
                ],
            )
            : (int) $this->value(
                'data.getCentralAssetDbStatistics.documentsAmount',
                /** @lang GraphQL */ <<<'GRAPHQL'
                query {
                    getCentralAssetDbStatistics {
                        documentsAmount
                    }
                }
                GRAPHQL,
            );
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
            'getDocuments',
            /** @lang GraphQL */ <<<GRAPHQL
            query getDocuments(\$limit: Int, \$lastId: String, \$from: String) {
                getDocuments(limit: \$limit, lastId: \$lastId, fromTimestamp: \$from) {
                    {$this->getDocumentPropertiesGraphQL()}
                }
            }
            GRAPHQL,
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
        return (int) $this->value(
            'data.getDocumentsByResellerCount',
            /** @lang GraphQL */ <<<'GRAPHQL'
            query value($id: String!, $from: String) {
                getDocumentsByResellerCount(resellerId: $id, fromTimestamp: $from)
            }
            GRAPHQL,
            [
                'id'   => $id,
                'from' => $this->datetime($from),
            ],
        );
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
            'getDocumentsByReseller',
            /** @lang GraphQL */ <<<GRAPHQL
            query getDocumentsByReseller(\$id: String!, \$limit: Int, \$lastId: String, \$from: String) {
                getDocumentsByReseller(resellerId: \$id, limit: \$limit, lastId: \$lastId, fromTimestamp: \$from) {
                    {$this->getDocumentPropertiesGraphQL()}
                }
            }
            GRAPHQL,
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
        return (int) $this->value(
            'data.getDocumentsByCustomerCount',
            /** @lang GraphQL */ <<<'GRAPHQL'
            query value($id: String!, $from: String) {
                getDocumentsByCustomerCount(customerId: $id, fromTimestamp: $from)
            }
            GRAPHQL,
            [
                'id'   => $id,
                'from' => $this->datetime($from),
            ],
        );
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
            'getDocumentsByCustomer',
            /** @lang GraphQL */ <<<GRAPHQL
            query getDocumentsByCustomer(\$id: String!, \$limit: Int, \$lastId: String, \$from: String) {
                getDocumentsByCustomer(customerId: \$id, limit: \$limit, lastId: \$lastId, fromTimestamp: \$from) {
                    {$this->getDocumentPropertiesGraphQL()}
                }
            }
            GRAPHQL,
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
            'getDocumentById',
            /** @lang GraphQL */ <<<GRAPHQL
            query getDocumentById(\$id: String!) {
                getDocumentById(id: \$id) {
                    {$this->getDocumentPropertiesGraphQL()}
                }
            }
            GRAPHQL,
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
        return (bool) $this->value(
            'data.updateBrandingData',
            /** @lang GraphQL */ <<<'GRAPHQL'
            mutation updateBrandingData($input: CompanyBrandingData!) {
                updateBrandingData(input: $input)
            }
            GRAPHQL,
            [
                'input' => $input,
            ],
        );
    }

    public function updateCompanyLogo(UpdateCompanyFile $input): ?string {
        return ((string) $this->value(
            'data.updateCompanyLogo',
            /** @lang GraphQL */ <<<'GRAPHQL'
            mutation updateCompanyLogo($input: UpdateCompanyFile!) {
                updateCompanyLogo(input: $input)
            }
            GRAPHQL,
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
            'data.updateCompanyFavicon',
            /** @lang GraphQL */ <<<'GRAPHQL'
            mutation updateCompanyFavicon($input: UpdateCompanyFile!) {
                updateCompanyFavicon(input: $input)
            }
            GRAPHQL,
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
            'data.updateCompanyMainImageOnTheRight',
            /** @lang GraphQL */ <<<'GRAPHQL'
            mutation updateCompanyMainImageOnTheRight($input: UpdateCompanyFile!) {
                updateCompanyMainImageOnTheRight(input: $input)
            }
            GRAPHQL,
            [
                'input' => $input->toArray(),
            ],
            [
                'input.file',
            ],
        )) ?: null;
    }

    protected function triggerCoverageStatusCheck(TriggerCoverageStatusCheck $input): bool {
        return (bool) $this->normalizer->boolean($this->value(
            'data.triggerCoverageStatusCheck',
            /** @lang GraphQL */ <<<'GRAPHQL'
            mutation triggerCoverageStatusCheck($input: TriggerCoverageStatusCheck!) {
                triggerCoverageStatusCheck(input: $input)
            }
            GRAPHQL,
            [
                'input' => $input->toArray(),
            ],
        ));
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
        string $selector,
        string $graphql,
        array $variables,
        Closure $retriever,
        int $limit = null,
        string|int|null $offset = null,
    ): ObjectIterator {
        return (new QueryIterator(
            OffsetBasedIterator::class,
            new Query($this, "data.{$selector}", $graphql, $variables),
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
        string $selector,
        string $graphql,
        array $variables,
        Closure $retriever,
        int $limit = null,
        string $lastId = null,
    ): ObjectIterator {
        return (new QueryIterator(
            LastIdBasedIterator::class,
            new Query($this, "data.{$selector}", $graphql, $variables),
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
    public function get(string $selector, string $graphql, array $variables, Closure $retriever): ?object {
        $results = (array) $this->call("data.{$selector}", $graphql, $variables);
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
        string $selector,
        string $graphql,
        array $variables = [],
        array $files = [],
    ): string|float|int|bool|null {
        $value = $this->call($selector, $graphql, $variables, $files);

        assert(is_scalar($value) || $value === null);

        return $value;
    }

    /**
     * @param array<string, mixed> $variables
     * @param array<string>        $files
     */
    public function call(string $selector, string $graphql, array $variables = [], array $files = []): mixed {
        $json   = $this->callExecute($selector, $graphql, $variables, $files);
        $json   = $this->callDump($selector, $graphql, $variables, $json);
        $errors = Arr::get($json, 'errors', Arr::get($json, 'error.errors'));
        $result = Arr::get($json, $selector);

        if ($errors) {
            $error = new GraphQLRequestFailed($graphql, $variables, $errors);

            $this->dispatcher->dispatch(new RequestFailed($selector, $graphql, $variables, $json));
            $this->handler->report($error);

            if (!$result) {
                throw $error;
            }
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

    /**
     * @param array<string, mixed> $variables
     */
    protected function callDump(string $selector, string $graphql, array $variables, mixed $json): mixed {
        return $json;
    }

    /**
     * @param array<string, mixed> $variables
     */
    protected function callDumpPath(string $selector, string $graphql, array $variables): string {
        $dump = implode('.', [sha1($graphql), sha1(json_encode($variables)), 'json']);
        $path = "{$selector}/{$dump}";

        return $path;
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

    // <editor-fold desc="GraphQL">
    // =========================================================================
    protected function getCompanyInfoGraphQL(): string {
        return <<<'GRAPHQL'
            id
            name
            status
            updatedAt
            companyType
            GRAPHQL;
    }

    protected function getCompanyContactPersonsGraphQL(): string {
        return <<<'GRAPHQL'
            companyContactPersons {
                phoneNumber
                name
                type
                mail
            }
            GRAPHQL;
    }

    protected function getCompanyLocationsGraphQL(): string {
        return <<<'GRAPHQL'
            locations {
                country
                countryCode
                latitude
                longitude
                city
                zip
                address
                locationType
            }
            GRAPHQL;
    }

    protected function getCompanyKpisGraphQL(): string {
        return <<<GRAPHQL
            companyKpis {
                {$this->getKpisGraphQL()}
            }
            GRAPHQL;
    }

    protected function getCompanyBrandingDataGraphQL(): string {
        return <<<'GRAPHQL'
            brandingData {
                brandingMode
                defaultLogoUrl
                defaultMainColor
                favIconUrl
                logoUrl
                mainColor
                mainImageOnTheRight
                resellerAnalyticsCode
                secondaryColor
                secondaryColorDefault
                useDefaultFavIcon
                mainHeadingText {
                    language_code
                    text
                }
                underlineText {
                    language_code
                    text
                }
            }
            GRAPHQL;
    }

    protected function getCompanyKeycloakGraphQL(): string {
        return <<<'GRAPHQL'
            keycloakName
            keycloakGroupId
            keycloakClientScopeName
            GRAPHQL;
    }

    protected function getResellerPropertiesGraphQL(): string {
        return <<<GRAPHQL
            {$this->getCompanyInfoGraphQL()}
            {$this->getCompanyContactPersonsGraphQL()}
            {$this->getCompanyLocationsGraphQL()}
            {$this->getCompanyKpisGraphQL()}
            {$this->getCompanyKeycloakGraphQL()}
            {$this->getCompanyBrandingDataGraphQL()}
            GRAPHQL;
    }

    protected function getCustomerPropertiesGraphQL(): string {
        return <<<GRAPHQL
            {$this->getCompanyInfoGraphQL()}
            {$this->getCompanyContactPersonsGraphQL()}
            {$this->getCompanyLocationsGraphQL()}
            {$this->getCompanyKpisGraphQL()}
            companyResellerKpis {
                resellerId
                {$this->getKpisGraphQL()}
            }
            GRAPHQL;
    }

    protected function getDistributorPropertiesGraphQL(): string {
        return $this->getCompanyInfoGraphQL();
    }

    protected function getAssetPropertiesGraphQL(): string {
        return <<<'GRAPHQL'
            id
            serialNumber

            assetSku
            assetSkuDescription
            assetTag
            assetType
            status
            vendor

            eolDate
            eosDate

            country
            countryCode
            latitude
            longitude
            zip
            city
            address
            address2

            customerId
            resellerId

            updatedAt

            latestContactPersons {
                phoneNumber
                name
                type
                mail
            }

            assetCoverage
            coverageStatusCheck {
                coverageStatus
                coverageStatusUpdatedAt
                coverageEntries {
                    coverageStartDate
                    coverageEndDate
                    type
                    description
                    status
                    serviceSku
                }
            }

            dataQualityScore
            GRAPHQL;
    }

    protected function getAssetDocumentsPropertiesGraphQL(): string {
        return <<<'GRAPHQL'
            assetDocument {
                startDate
                endDate
                documentNumber

                document {
                    id
                    type
                    documentNumber

                    startDate
                    endDate

                    currencyCode
                    languageCode
                    totalNetPrice

                    updatedAt

                    vendorSpecificFields {
                        vendor
                        groupId
                        groupDescription
                        said
                    }

                    contactPersons {
                        phoneNumber
                        name
                        type
                        mail
                    }

                    customerId
                    resellerId
                    distributorId
                }

                serviceGroupSku
                serviceGroupSkuDescription
                serviceLevelSku
                serviceLevelSkuDescription
                serviceFullDescription

                customer {
                  id
                }

                reseller {
                  id
                }
            }
            GRAPHQL;
    }

    protected function getDocumentPropertiesGraphQL(): string {
        return <<<'GRAPHQL'
            id
            type
            status
            documentNumber
            startDate
            endDate
            currencyCode
            totalNetPrice
            languageCode
            updatedAt
            resellerId
            customerId
            distributorId

            vendorSpecificFields {
                vendor
                groupId
                groupDescription
                said
            }

            contactPersons {
                phoneNumber
                name
                type
                mail
            }

            documentEntries {
                assetId
                assetProductLine
                assetProductGroupDescription
                serviceGroupSku
                serviceGroupSkuDescription
                serviceLevelSku
                serviceLevelSkuDescription
                serviceFullDescription
                startDate
                endDate
                languageCode
                currencyCode
                listPrice
                estimatedValueRenewal
                assetProductType
                environmentId
                equipmentNumber
                lineItemListPrice
                lineItemMonthlyRetailPrice
                said
                sarNumber
                pspId
                pspName
            }
            GRAPHQL;
    }

    protected function getKpisGraphQL(): string {
        return <<<'GRAPHQL'
            totalAssets
            activeAssets
            activeAssetsPercentage
            activeCustomers
            newActiveCustomers
            activeContracts
            activeContractTotalAmount
            newActiveContracts
            expiringContracts
            activeQuotes
            activeQuotesTotalAmount
            newActiveQuotes
            expiringQuotes
            expiredQuotes
            expiredContracts
            orderedQuotes
            acceptedQuotes
            requestedQuotes
            receivedQuotes
            rejectedQuotes
            awaitingQuotes
            activeAssetsOnContract
            activeAssetsOnWarranty
            activeExposedAssets
            serviceRevenueTotalAmount
            serviceRevenueTotalAmountChange
            GRAPHQL;
    }
    //</editor-fold>
}
