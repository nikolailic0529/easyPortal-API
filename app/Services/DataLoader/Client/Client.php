<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use App\Services\DataLoader\Client\Events\RequestFailed;
use App\Services\DataLoader\Client\Events\RequestStarted;
use App\Services\DataLoader\Client\Events\RequestSuccessful;
use App\Services\DataLoader\Client\Exceptions\DataLoaderDisabled;
use App\Services\DataLoader\Client\Exceptions\DataLoaderUnavailable;
use App\Services\DataLoader\Client\Exceptions\GraphQLRequestFailed;
use App\Services\DataLoader\Client\Exceptions\GraphQLSlowQuery;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\CompanyBrandingData;
use App\Services\DataLoader\Schema\Document;
use App\Services\DataLoader\Schema\TriggerCoverageStatusCheck;
use App\Services\DataLoader\Schema\UpdateCompanyFile;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\DataLoader\Testing\Data\ClientDump;
use App\Services\DataLoader\Testing\Data\ClientDumpFile;
use App\Utils\Iterators\ObjectIterator;
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
use function array_merge;
use function explode;
use function implode;
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
    public function getDistributorsCount(): int {
        return (int) $this->call(
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
     * @return \App\Services\DataLoader\Client\LastIdBasedIterator<\App\Services\DataLoader\Schema\Company>
     */
    public function getDistributors(
        DateTimeInterface $from = null,
        int $limit = null,
        string $lastId = null,
    ): LastIdBasedIterator {
        return $this
            ->getLastIdBasedIterator(
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
            )
            ->setLimit($limit)
            ->setOffset($lastId);
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

    public function getResellersCount(): int {
        return (int) $this->call(
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
     * @return \App\Services\DataLoader\Client\LastIdBasedIterator<\App\Services\DataLoader\Schema\Company>
     */
    public function getResellers(
        DateTimeInterface $from = null,
        int $limit = null,
        string $lastId = null,
    ): LastIdBasedIterator {
        return $this
            ->getLastIdBasedIterator(
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
            )
            ->setLimit($limit)
            ->setOffset($lastId);
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

    public function getCustomersCount(): int {
        return (int) $this->call(
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
     * @return \App\Services\DataLoader\Client\LastIdBasedIterator<\App\Services\DataLoader\Schema\Company>
     */
    public function getCustomers(
        DateTimeInterface $from = null,
        int $limit = null,
        string $lastId = null,
    ): LastIdBasedIterator {
        return $this
            ->getLastIdBasedIterator(
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
            )
            ->setLimit($limit)
            ->setOffset($lastId);
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

    public function getAssetsCount(): int {
        return (int) $this->call(
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

    /**
     * @return \App\Services\DataLoader\Client\LastIdBasedIterator<\App\Services\DataLoader\Schema\ViewAsset>
     */
    public function getAssetsByCustomerId(string $id, int $limit = null, string $lastId = null): LastIdBasedIterator {
        return $this
            ->getLastIdBasedIterator(
                'getAssetsByCustomerId',
                /** @lang GraphQL */ <<<GRAPHQL
                query items(\$id: String!, \$limit: Int, \$lastId: String) {
                    getAssetsByCustomerId(customerId: \$id, limit: \$limit, lastId: \$lastId) {
                        {$this->getAssetPropertiesGraphQL()}
                    }
                }
                GRAPHQL,
                [
                    'id' => $id,
                ],
                $this->getAssetRetriever(),
            )
            ->setLimit($limit)
            ->setOffset($lastId);
    }

    /**
     * @return \App\Services\DataLoader\Client\LastIdBasedIterator<\App\Services\DataLoader\Schema\ViewAsset>
     */
    public function getAssetsByCustomerIdWithDocuments(
        string $id,
        int $limit = null,
        string $lastId = null,
    ): LastIdBasedIterator {
        return $this
            ->getLastIdBasedIterator(
                'getAssetsByCustomerId',
                /** @lang GraphQL */ <<<GRAPHQL
                query items(\$id: String!, \$limit: Int, \$lastId: String) {
                    getAssetsByCustomerId(customerId: \$id, limit: \$limit, lastId: \$lastId) {
                        {$this->getAssetPropertiesGraphQL()}
                        {$this->getAssetDocumentsPropertiesGraphQL()}
                    }
                }
                GRAPHQL,
                [
                    'id' => $id,
                ],
                $this->getAssetRetriever(),
            )
            ->setLimit($limit)
            ->setOffset($lastId);
    }

    /**
     * @return \App\Services\DataLoader\Client\LastIdBasedIterator<\App\Services\DataLoader\Schema\ViewAsset>
     */
    public function getAssetsByResellerId(string $id, int $limit = null, string $lastId = null): LastIdBasedIterator {
        return $this
            ->getLastIdBasedIterator(
                'getAssetsByResellerId',
                /** @lang GraphQL */ <<<GRAPHQL
                query items(\$id: String!, \$limit: Int, \$lastId: String) {
                    getAssetsByResellerId(resellerId: \$id, limit: \$limit, lastId: \$lastId) {
                        {$this->getAssetPropertiesGraphQL()}
                    }
                }
                GRAPHQL,
                [
                    'id' => $id,
                ],
                $this->getAssetRetriever(),
            )
            ->setLimit($limit)
            ->setOffset($lastId);
    }

    /**
     * @return \App\Services\DataLoader\Client\LastIdBasedIterator<\App\Services\DataLoader\Schema\ViewAsset>
     */
    public function getAssetsByResellerIdWithDocuments(
        string $id,
        int $limit = null,
        string $lastId = null,
    ): LastIdBasedIterator {
        return $this
            ->getLastIdBasedIterator(
                'getAssetsByResellerId',
                /** @lang GraphQL */ <<<GRAPHQL
                query items(\$id: String!, \$limit: Int, \$lastId: String) {
                    getAssetsByResellerId(resellerId: \$id, limit: \$limit, lastId: \$lastId) {
                        {$this->getAssetPropertiesGraphQL()}
                        {$this->getAssetDocumentsPropertiesGraphQL()}
                    }
                }
                GRAPHQL,
                [
                    'id' => $id,
                ],
                $this->getAssetRetriever(),
            )
            ->setLimit($limit)
            ->setOffset($lastId);
    }

    /**
     * @return \App\Utils\Iterators\ObjectIterator<\App\Services\DataLoader\Schema\ViewAsset>
     */
    public function getAssets(
        DateTimeInterface $from = null,
        int $limit = null,
        string $offset = null,
    ): ObjectIterator {
        return $this
            ->getLastIdBasedIterator(
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
            )
            ->setLimit($limit)
            ->setOffset($offset);
    }

    /**
     * @return \App\Utils\Iterators\ObjectIterator<\App\Services\DataLoader\Schema\ViewAsset>
     */
    public function getAssetsWithDocuments(
        DateTimeInterface $from = null,
        int $limit = null,
        string $offset = null,
    ): ObjectIterator {
        return $this
            ->getLastIdBasedIterator(
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
            )
            ->setLimit($limit)
            ->setOffset($offset);
    }

    public function getDocumentsCount(): int {
        return (int) $this->call(
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
     * @return \App\Utils\Iterators\ObjectIterator<\App\Services\DataLoader\Schema\Document>
     */
    public function getDocuments(
        DateTimeInterface $from = null,
        int $limit = null,
        string $offset = null,
    ): ObjectIterator {
        return $this
            ->getLastIdBasedIterator(
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
            )
            ->setLimit($limit)
            ->setOffset($offset);
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
        return (bool) $this->call(
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
        return $this->call(
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
        );
    }

    public function updateCompanyFavicon(UpdateCompanyFile $input): ?string {
        return $this->call(
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
        );
    }

    public function updateCompanyMainImageOnTheRight(UpdateCompanyFile $input): ?string {
        return $this->call(
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
        );
    }

    public function triggerCoverageStatusCheck(TriggerCoverageStatusCheck $input): bool {
        return (bool) $this->normalizer->boolean($this->call(
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
     * @param array<mixed> $params
     * @param \Closure(array<mixed>):T $reriever
     *
     * @return \App\Services\DataLoader\Client\OffsetBasedIterator<T>
     */
    public function getOffsetBasedIterator(
        string $selector,
        string $graphql,
        array $params,
        Closure $retriever,
    ): OffsetBasedIterator {
        return (new OffsetBasedIterator(
            $this->handler,
            function (array $variables) use ($selector, $graphql, $params) {
                return $this->call("data.{$selector}", $graphql, array_merge($params, $variables));
            },
            $retriever,
        ))
            ->setChunkSize($this->config->get('ep.data_loader.chunk'));
    }

    /**
     * @template T
     *
     * @param array<mixed> $params
     * @param \Closure(array<mixed>):T $reriever
     *
     * @return \App\Services\DataLoader\Client\LastIdBasedIterator<T>
     */
    public function getLastIdBasedIterator(
        string $selector,
        string $graphql,
        array $params,
        Closure $retriever,
    ): LastIdBasedIterator {
        return (new LastIdBasedIterator(
            $this->handler,
            function (array $variables) use ($selector, $graphql, $params) {
                return $this->call("data.{$selector}", $graphql, array_merge($params, $variables));
            },
            $retriever,
        ))
            ->setChunkSize($this->config->get('ep.data_loader.chunk'));
    }

    /**
     * @template T
     *
     * @param array<mixed> $params
     * @param \Closure(array<mixed>): T $retriever
     *
     * @return T|null
     */
    public function get(string $selector, string $graphql, array $params, Closure $retriever): ?object {
        $results = (array) $this->call("data.{$selector}", $graphql, $params);
        $item    = array_is_list($results) ? (reset($results) ?: null) : $results;

        if ($item) {
            $item = $retriever($item);
        }

        return $item;
    }

    /**
     * @param array<mixed>  $params
     * @param array<string> $files
     */
    public function call(string $selector, string $graphql, array $params = [], array $files = []): mixed {
        $json   = $this->callExecute($selector, $graphql, $params, $files);
        $errors = Arr::get($json, 'errors', Arr::get($json, 'error.errors'));
        $result = Arr::get($json, $selector);

        if ($errors) {
            $error = new GraphQLRequestFailed($graphql, $params, $errors);

            $this->dispatcher->dispatch(new RequestFailed($selector, $graphql, $params, $json));
            $this->handler->report($error);

            if (!$result) {
                throw $error;
            }
        } else {
            $this->dispatcher->dispatch(new RequestSuccessful($selector, $graphql, $params, $json));
        }

        // Dump
        $this->callDump($selector, $graphql, $params, $json);

        // Return
        return $result;
    }

    /**
     * @param array<mixed>  $params
     * @param array<string> $files
     */
    protected function callExecute(string $selector, string $graphql, array $params, array $files): mixed {
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
        $request = $this->client->timeout($timeout)->withHeaders($headers);
        $data    = $this->callData($selector, $graphql, $params, $files, $request, [
            'query'     => $graphql,
            'variables' => $params,
        ]);

        // Call
        $json  = null;
        $begin = time();

        try {
            $this->dispatcher->dispatch(new RequestStarted($selector, $graphql, $params));

            $response = $request->post($url, $data);
            $json     = $response->json();

            $response->throw();
        } catch (ConnectionException $exception) {
            $this->dispatcher->dispatch(new RequestFailed($selector, $graphql, $params, null, $exception));

            throw new DataLoaderUnavailable($exception);
        } catch (Exception $exception) {
            $error = new GraphQLRequestFailed($graphql, $params, [], $exception);

            $this->dispatcher->dispatch(new RequestFailed($selector, $graphql, $params, null, $exception));
            $this->handler->report($error);

            throw $error;
        }

        // Slow log
        $slowlog = (int) $this->config->get('ep.data_loader.slowlog', 0);
        $time    = time() - $begin;

        if ($slowlog > 0 && $time >= $slowlog) {
            $this->handler->report(new GraphQLSlowQuery($graphql, $params, $time, $slowlog));
        }

        // Return
        return $json;
    }

    /**
     * @param array<mixed>  $params
     * @param array<string> $files
     * @param array<mixed>  $data
     *
     * @return array<mixed>
     */
    protected function callData(
        string $selector,
        string $graphql,
        array $params,
        array $files,
        PendingRequest $request,
        array $data,
    ): array {
        if ($files) {
            $map       = [];
            $index     = 0;
            $variables = $params;

            foreach ($files as $variable) {
                $name = 'file'.$index;
                $file = Arr::get($params, $variable);

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

    protected function callDump(string $selector, string $graphql, mixed $params, mixed $json): void {
        // Enabled?
        if (!$this->config->get('ep.data_loader.dump')) {
            return;
        }

        // Dump
        $path = "{$this->config->get('ep.data_loader.dump')}/{$this->callDumpPath($selector, $graphql, $params)}";
        $dump = new ClientDumpFile(new SplFileInfo($path));

        $dump->setDump(new ClientDump([
            'selector' => $selector,
            'graphql'  => $graphql,
            'params'   => $params,
            'response' => $json,
        ]));

        $dump->save();
    }

    protected function callDumpPath(string $selector, string $graphql, mixed $params): string {
        $dump = implode('.', [sha1($graphql), sha1(json_encode($params)), 'json']);
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
     * @return \Closure(array<mixed>): \App\Services\DataLoader\Schema\Company
     */
    protected function getCompanyRetriever(): Closure {
        return static function (array $data): Company {
            return new Company($data);
        };
    }

    /**
     * @return \Closure(array<mixed>): \App\Services\DataLoader\Schema\ViewAsset
     */
    protected function getAssetRetriever(): Closure {
        return static function (array $data): ViewAsset {
            return new ViewAsset($data);
        };
    }

    /**
     * @return \Closure(array<mixed>): \App\Services\DataLoader\Schema\Document
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
            companyTypes {
                type
                status
            }
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
        return <<<'GRAPHQL'
            companyKpis {
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
                mainHeadingText
                mainImageOnTheRight
                resellerAnalyticsCode
                secondaryColor
                secondaryColorDefault
                underlineText
                useDefaultFavIcon
            }
            GRAPHQL;
    }

    protected function getCompanyKeyCloakGraphQL(): string {
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
            {$this->getCompanyKeyCloakGraphQL()}
            {$this->getCompanyBrandingDataGraphQL()}
            GRAPHQL;
    }

    protected function getCustomerPropertiesGraphQL(): string {
        return <<<GRAPHQL
            {$this->getCompanyInfoGraphQL()}
            {$this->getCompanyContactPersonsGraphQL()}
            {$this->getCompanyLocationsGraphQL()}
            {$this->getCompanyKpisGraphQL()}
            GRAPHQL;
    }

    protected function getDistributorPropertiesGraphQL(): string {
        return $this->getCompanyInfoGraphQL();
    }

    protected function getAssetPropertiesGraphQL(): string {
        return <<<'GRAPHQL'
            id
            serialNumber

            productDescription
            assetTag
            assetType
            status
            vendor
            sku

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

                currencyCode
                languageCode
                netPrice
                discount
                listPrice

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

                skuNumber
                supportPackage
                warrantyEndDate
                estimatedValueRenewal

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
            GRAPHQL;
    }
    //</editor-fold>
}
