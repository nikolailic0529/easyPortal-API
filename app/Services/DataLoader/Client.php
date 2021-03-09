<?php declare(strict_types = 1);

namespace App\Services\DataLoader;

use App\Services\DataLoader\Schema\Asset;
use App\Services\DataLoader\Schema\Company;
use Closure;
use Generator;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\Factory;

use function array_merge;
use function count;
use function reset;

class Client {
    protected const CONFIG = 'data-loader';

    public function __construct(
        protected Repository $config,
        protected Factory $client,
    ) {
        // empty
    }

    // <editor-fold desc="API">
    // =========================================================================
    public function isEnabled(): bool {
        return $this->setting('enabled')
            && $this->setting('endpoint');
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
     * @return \Generator<\App\Services\DataLoader\Schema\Asset>
     */
    public function getAssetsByCustomerId(string $id, int $limit = null): Generator {
        return $this->iterator(
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
                'id'    => $id,
                'limit' => $limit,
            ],
            static function (array $data): Asset {
                return Asset::create($data);
            },
        );
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    /**
     * @template T of \App\Services\DataLoader\Schema\Type
     *
     * @param array<mixed> $params
     * @param \Closure(array<mixed>):T $reriever
     *
     * @return \Generator<T>
     */
    protected function iterator(string $selector, string $graphql, array $params, Closure $retriever): Generator {
        $offset = 0;

        do {
            $items  = $this->call($selector, $graphql, array_merge($params, [
                'offset' => $offset,
                'limit'  => $this->setting('limit'),
            ]));
            $offset = $offset + count($items);

            foreach ($items as $item) {
                yield $retriever($item);
            }
        } while ($items);
    }

    /**
     * @param array<mixed> $params
     *
     * @return array<mixed>|null
     */
    protected function get(string $selector, string $graphql, array $params = []): ?array {
        $results = $this->call($selector, $graphql, $params);
        $item    = reset($results) ?: null;

        return $item;
    }

    /**
     * @param array<mixed> $params
     *
     * @return array<mixed>|null
     */
    protected function call(string $selector, string $graphql, array $params = []): ?array {
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

        // Return
        return $json['data'][$selector] ?? [];
    }

    protected function setting(string $name, mixed $default = null): mixed {
        return $this->config->get(static::CONFIG.'.'.$name, $default);
    }
    // </editor-fold>
}
