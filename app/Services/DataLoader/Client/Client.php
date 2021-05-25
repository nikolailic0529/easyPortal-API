<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use App\Services\DataLoader\Exceptions\DataLoaderException;
use App\Services\DataLoader\Exceptions\GraphQLQueryFailed;
use App\Services\DataLoader\Schema\Asset;
use App\Services\DataLoader\Schema\Company;
use Closure;
use Exception;
use GraphQL\Type\Introspection;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Arr;
use Psr\Log\LoggerInterface;

use function reset;
use function time;

class Client {
    public function __construct(
        protected ExceptionHandler $handler,
        protected LoggerInterface $logger,
        protected Repository $config,
        protected Factory $client,
        protected Token $token,
    ) {
        // empty
    }

    // <editor-fold desc="Queries">
    // =========================================================================
    /**
     * @return \App\Services\DataLoader\Client\QueryIterator<\App\Services\DataLoader\Schema\Company>
     */
    public function getResellers(int $limit = null, int $offset = 0): QueryIterator {
        return $this
            ->iterator(
                'getResellers',
                /** @lang GraphQL */ <<<GRAPHQL
                query items(\$limit: Int, \$offset: Int) {
                    getResellers(limit: \$limit, offset: \$offset) {
                        {$this->getCompanyPropertiesGraphQL()}
                    }
                }
                GRAPHQL,
                [],
                static function (array $data): Company {
                    return Company::create($data);
                },
            )
            ->limit($limit)
            ->offset($offset);
    }

    public function getCompanyById(string $id): ?Company {
        $company = $this->get(
            'getCompanyById',
            /** @lang GraphQL */ <<<GRAPHQL
            query getCompanyById(\$id: String!) {
                getCompanyById(id: \$id) {
                    {$this->getCompanyPropertiesGraphQL()}
                }
            }
            GRAPHQL,
            [
                'id' => $id,
            ],
        );

        if ($company) {
            $company = Company::create($company);
        }

        return $company;
    }

    public function getAssetById(string $id): ?Asset {
        $asset = $this->get(
            'getAssets',
            /** @lang GraphQL */ <<<GRAPHQL
            query getAssets(\$id: String!) {
                getAssets(args: [{key: "id", value: \$id}]) {
                    {$this->getAssetPropertiesGraphQL()}
                    reseller {
                        {$this->getCompanyPropertiesGraphQL()}
                    }
                    customer {
                        {$this->getCompanyPropertiesGraphQL()}
                    }
                }
            }
            GRAPHQL,
            [
                'id' => $id,
            ],
        );

        if ($asset) {
            $asset = Asset::create($asset);
        }

        return $asset;
    }

    /**
     * @return \App\Services\DataLoader\Client\QueryIterator<\App\Services\DataLoader\Schema\Asset>
     */
    public function getAssetsByCustomerId(string $id, int $limit = null, int $offset = 0): QueryIterator {
        return $this
            ->iterator(
                'getAssetsByCustomerId',
                /** @lang GraphQL */ <<<GRAPHQL
                query items(\$id: String!, \$limit: Int, \$offset: Int) {
                    getAssetsByCustomerId(customerId: \$id, limit: \$limit, offset: \$offset) {
                        {$this->getAssetPropertiesGraphQL()}
                        reseller {
                            {$this->getCompanyPropertiesGraphQL()}
                        }
                    }
                }
                GRAPHQL,
                [
                    'id' => $id,
                ],
                static function (array $data): Asset {
                    return Asset::create($data);
                },
            )
            ->limit($limit)
            ->offset($offset);
    }

    /**
     * @return \App\Services\DataLoader\Client\QueryIterator<\App\Services\DataLoader\Schema\Asset>
     */
    public function getAssetsByCustomerIdWithDocuments(string $id, int $limit = null, int $offset = 0): QueryIterator {
        return $this
            ->iterator(
                'getAssetsByCustomerId',
                /** @lang GraphQL */ <<<GRAPHQL
                query items(\$id: String!, \$limit: Int, \$offset: Int) {
                    getAssetsByCustomerId(customerId: \$id, limit: \$limit, offset: \$offset) {
                        {$this->getAssetPropertiesGraphQL()}
                        reseller {
                            {$this->getCompanyPropertiesGraphQL()}
                        }
                        assetDocument {
                            {$this->getAssetDocumentsPropertiesGraphQL()}
                        }
                    }
                }
                GRAPHQL,
                [
                    'id' => $id,
                ],
                static function (array $data): Asset {
                    return Asset::create($data);
                },
            )
            ->limit($limit)
            ->offset($offset);
    }

    /**
     * @return \App\Services\DataLoader\Client\QueryIterator<\App\Services\DataLoader\Schema\Asset>
     */
    public function getAssetsByResellerId(string $id, int $limit = null, int $offset = 0): QueryIterator {
        return $this
            ->iterator(
                'getAssetsByResellerId',
                /** @lang GraphQL */ <<<GRAPHQL
                query items(\$id: String!, \$limit: Int, \$offset: Int) {
                    getAssetsByResellerId(resellerId: \$id, limit: \$limit, offset: \$offset) {
                        {$this->getAssetPropertiesGraphQL()}
                        customer {
                            {$this->getCompanyPropertiesGraphQL()}
                        }
                    }
                }
                GRAPHQL,
                [
                    'id' => $id,
                ],
                static function (array $data): Asset {
                    return Asset::create($data);
                },
            )
            ->limit($limit)
            ->offset($offset);
    }

    /**
     * @return \App\Services\DataLoader\Client\QueryIterator<\App\Services\DataLoader\Schema\Asset>
     */
    public function getAssetsByResellerIdWithDocuments(string $id, int $limit = null, int $offset = 0): QueryIterator {
        return $this
            ->iterator(
                'getAssetsByResellerId',
                /** @lang GraphQL */ <<<GRAPHQL
                query items(\$id: String!, \$limit: Int, \$offset: Int) {
                    getAssetsByResellerId(resellerId: \$id, limit: \$limit, offset: \$offset) {
                        {$this->getAssetPropertiesGraphQL()}
                        customer {
                            {$this->getCompanyPropertiesGraphQL()}
                        }
                        assetDocument {
                            {$this->getAssetDocumentsPropertiesGraphQL()}
                        }
                    }
                }
                GRAPHQL,
                [
                    'id' => $id,
                ],
                static function (array $data): Asset {
                    return Asset::create($data);
                },
            )
            ->limit($limit)
            ->offset($offset);
    }

    /**
     * @return array<mixed>
     */
    public function getIntrospection(): array {
        return $this->call('data', Introspection::getIntrospectionQuery());
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
     * @return \App\Services\DataLoader\Client\QueryIterator<T>
     */
    public function iterator(string $selector, string $graphql, array $params, Closure $retriever): QueryIterator {
        return (new QueryIterator($this->logger, $this, "data.{$selector}", $graphql, $params, $retriever))
            ->chunk($this->config->get('ep.data_loader.chunk'));
    }

    /**
     * @param array<mixed> $params
     *
     * @return array<mixed>|null
     */
    public function get(string $selector, string $graphql, array $params = []): ?array {
        $results = $this->call("data.{$selector}", $graphql, $params);
        $item    = reset($results) ?: null;

        return $item;
    }

    /**
     * @param array<mixed> $params
     *
     * @return array<mixed>|null
     */
    public function call(string $selector, string $graphql, array $params = []): ?array {
        // Enabled?
        if (!$this->isEnabled()) {
            throw new DataLoaderException('DataLoader is disabled.');
        }

        // Call
        $url     = $this->config->get('ep.data_loader.endpoint') ?: $this->config->get('ep.data_loader.url');
        $timeout = $this->config->get('ep.data_loader.timeout') ?: 5 * 60;
        $data    = [
            'query'     => $graphql,
            'variables' => $params,
        ];
        $headers = [
            'Accept'        => 'application/json',
            'Authorization' => "Bearer {$this->token->getAccessToken()}",
        ];
        $begin   = time();

        try {
            $response = $this->client
                ->timeout($timeout)
                ->withHeaders($headers)
                ->post($url, $data);

            $response->throw();
        } catch (Exception $exception) {
            $error = new GraphQLQueryFailed($graphql, $params, [], $exception);

            $this->handler->report($error);

            throw $error;
        }

        // Slow log
        $slowlog = (int) $this->config->get('ep.data_loader.slowlog', 0);
        $time    = time() - $begin;

        if ($slowlog > 0 && $time >= $slowlog) {
            $this->logger->info('DataLoader: Slow query detected.', [
                'threshold' => $slowlog,
                'time'      => $time,
                'graphql'   => $graphql,
                'params'    => $params,
            ]);
        }

        // Error?
        $json   = $response->json();
        $errors = Arr::get($json, 'errors', Arr::get($json, 'error.errors'));
        $result = Arr::get($json, $selector, []) ?: [];

        if ($errors) {
            $error = new GraphQLQueryFailed($graphql, $params, $errors);

            $this->handler->report($error);

            if (!$result) {
                throw $error;
            }
        }

        // Return
        return $result;
    }
    // </editor-fold>

    // <editor-fold desc="GraphQL">
    // =========================================================================
    protected function getCompanyPropertiesGraphQL(): string {
        return <<<'GRAPHQL'
            id
            name
            keycloakGroupId
            keycloakName
            companyContactPersons {
                phoneNumber
                name
                type
                mail
            }
            companyTypes {
                type
                status
            }
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

            latestContactPersons {
                phoneNumber
                name
                type
                mail
            }

            assetCoverage
            GRAPHQL;
    }

    protected function getAssetDocumentsPropertiesGraphQL(): string {
        return <<<GRAPHQL
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

                vendorSpecificFields {
                    vendor
                }

                contactPersons {
                    phoneNumber
                    name
                    type
                    mail
                }

                customer {
                  {$this->getCompanyPropertiesGraphQL()}
                }

                reseller {
                  {$this->getCompanyPropertiesGraphQL()}
                }
            }

            skuNumber
            skuDescription

            supportPackage
            supportPackageDescription

            warrantyEndDate

            estimatedValueRenewal

            customer {
              {$this->getCompanyPropertiesGraphQL()}
            }

            reseller {
              {$this->getCompanyPropertiesGraphQL()}
            }
            GRAPHQL;
    }
    //</editor-fold>
}
