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
use Traversable;

use function reset;

class Client {
    protected const CONFIG = 'data-loader';

    public function __construct(
        protected Repository $config,
        protected Factory $client,
    ) {
        // empty
    }

    // <editor-fold desc="Queries">
    // =========================================================================
    /**
     * @return \Traversable<\App\Services\DataLoader\Schema\Company>
     */
    public function getResellers(int $limit = null, int $offset = null): Traversable {
        $this
            ->iterator(
                'getResellers',
                /** @lang GraphQL */ <<<'GRAPHQL'
                query items($limit: Int, $offset: Int) {
                    getResellers(limit: $limit, offset: $offset) {
                        id
                        name
                        companyContactPersons {
                            phoneNumber
                            vendor
                            name
                            type
                        }
                        companyTypes {
                            vendorSpecificId
                            vendor
                            type
                            status
                        }
                        locations {
                            zip
                            address
                            city
                            locationType
                        }
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
            /** @lang GraphQL */ <<<'GRAPHQL'
            query getCompanyById($id: String!) {
                getCompanyById(id: $id) {
                    id
                    name
                    companyContactPersons {
                        phoneNumber
                        vendor
                        name
                        type
                    }
                    companyTypes {
                        vendorSpecificId
                        vendor
                        type
                        status
                    }
                    locations {
                        zip
                        address
                        city
                        locationType
                    }
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
            /** @lang GraphQL */ <<<'GRAPHQL'
            query getAssets($id: String!) {
                getAssets(args: [{key: "id", value: $id}]) {
                    id
                    name
                    companyContactPersons {
                        phoneNumber
                        vendor
                        name
                        type
                    }
                    companyTypes {
                        vendorSpecificId
                        vendor
                        type
                        status
                    }
                    locations {
                        zip
                        address
                        city
                        locationType
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
     * @return \Traversable<\App\Services\DataLoader\Schema\Asset>
     */
    public function getAssetsByCustomerId(string $id, int $limit = null, int $offset = null): Traversable {
        return $this
            ->iterator(
                'getAssetsByCustomerId',
                /** @lang GraphQL */ <<<'GRAPHQL'
                query items($id: String!, $limit: Int, $offset: Int) {
                    getAssetsByCustomerId(customerId: $id, limit: $limit, offset: $offset) {
                        id
                        serialNumber

                        productDescription
                        description
                        assetTag
                        assetType
                        vendor
                        sku

                        eolDate
                        eosDate

                        zip
                        city
                        address
                        address2

                        customerId
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
        return $this->setting('enabled')
            && $this->setting('endpoint');
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
        return (new QueryIterator($this, $selector, $graphql, $params, $retriever))->chunk($this->setting('chunk'));
    }

    /**
     * @param array<mixed> $params
     *
     * @return array<mixed>|null
     */
    public function get(string $selector, string $graphql, array $params = []): ?array {
        $results = $this->call($selector, $graphql, $params);
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
        $url      = $this->setting('endpoint');
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
            throw (new GraphQLQueryFailedException())->setErrors($errors);
        }

        // Return
        return Arr::get($json, $selector) ?: [];
    }

    protected function setting(string $name, mixed $default = null): mixed {
        return $this->config->get(static::CONFIG.'.'.$name, $default);
    }
    // </editor-fold>
}
