<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use App\Services\DataLoader\Exceptions\DataLoaderException;
use App\Services\DataLoader\Exceptions\GraphQLQueryFailedException;
use App\Services\DataLoader\Schema\Asset;
use App\Services\DataLoader\Schema\Company;
use Closure;
use GraphQL\Type\Introspection;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Arr;
use Psr\Log\LoggerInterface;

use function reset;

class Client {
    public function __construct(
        protected LoggerInterface $logger,
        protected Repository $config,
        protected Factory $client,
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
    public function getAssetsWithDocumentsByCustomerId(string $id, int $limit = null, int $offset = 0): QueryIterator {
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
    public function getAssetsWithDocumentsByResellerId(string $id, int $limit = null, int $offset = 0): QueryIterator {
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
            && $this->config->get('ep.data_loader.endpoint');
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
        $url      = $this->config->get('ep.data_loader.endpoint');
        $data     = [
            'query'     => $graphql,
            'variables' => $params,
        ];
        $headers  = [
            'Accept' => 'application/json',
        ];
        $response = $this->client
            ->withHeaders($headers)
            ->post($url, $data);
        $json     = $response->json();

        // Error?
        $errors = Arr::get($json, 'errors', Arr::get($json, 'error.errors'));

        if ($errors) {
            $this->logger->error('GraphQL request failed.', [
                'selector' => $selector,
                'graphql'  => $graphql,
                'params'   => $params,
                'errors'   => $errors,
            ]);

            if (!Arr::has($json, $selector) && Arr::get($json, $selector)) {
                throw new GraphQLQueryFailedException('GraphQL request failed.');
            }
        }

        // Return
        return Arr::get($json, $selector) ?: [];
    }
    // </editor-fold>

    // <editor-fold desc="GraphQL">
    // =========================================================================
    protected function getCompanyPropertiesGraphQL(): string {
        return <<<'GRAPHQL'
            id
            name
            companyContactPersons {
                phoneNumber
                name
                type
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
            GRAPHQL;
    }

    protected function getAssetDocumentsPropertiesGraphQL(): string {
        return <<<'GRAPHQL'
            startDate
            endDate
            documentNumber

            currencyCode

            document {
                id
                type
                documentNumber
                customerId
                resellerId

                startDate
                endDate

                currencyCode
                totalNetPrice

                vendorSpecificFields {
                    vendor
                }
            }

            skuNumber
            skuDescription

            supportPackage
            supportPackageDescription

            warrantyEndDate
            GRAPHQL;
    }
    //</editor-fold>
}
