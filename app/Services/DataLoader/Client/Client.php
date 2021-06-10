<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use App\Services\DataLoader\Client\Events\RequestFailed;
use App\Services\DataLoader\Client\Events\RequestStarted;
use App\Services\DataLoader\Client\Events\RequestSuccessful;
use App\Services\DataLoader\Client\Exceptions\DataLoaderDisabled;
use App\Services\DataLoader\Client\Exceptions\DataLoaderUnavailable;
use App\Services\DataLoader\Client\Exceptions\GraphQLRequestFailed;
use App\Services\DataLoader\Schema\Asset;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\CompanyBrandingData;
use App\Services\DataLoader\Schema\UpdateCompanyFile;
use Closure;
use Exception;
use GraphQL\Type\Introspection;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Arr;
use Psr\Log\LoggerInterface;
use SplFileInfo;

use function explode;
use function json_encode;
use function reset;
use function time;

class Client {
    public function __construct(
        protected ExceptionHandler $handler,
        protected LoggerInterface $logger,
        protected Dispatcher $dispatcher,
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
                    return new Company($data);
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
            $company = new Company($company);
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
                    customer {
                        {$this->getCustomerPropertiesGraphQL()}
                    }
                }
            }
            GRAPHQL,
            [
                'id' => $id,
            ],
        );

        if ($asset) {
            $asset = new Asset($asset);
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
                    }
                }
                GRAPHQL,
                [
                    'id' => $id,
                ],
                static function (array $data): Asset {
                    return new Asset($data);
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
                    return new Asset($data);
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
                            {$this->getCustomerPropertiesGraphQL()}
                        }
                    }
                }
                GRAPHQL,
                [
                    'id' => $id,
                ],
                static function (array $data): Asset {
                    return new Asset($data);
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
                            {$this->getCustomerPropertiesGraphQL()}
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
                    return new Asset($data);
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
            mutation updateCompanyLogo($input: UpdateCompanyLogo!) {
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
            mutation updateCompanyFavicon($input: UpdateCompanyFavicon!) {
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
            mutation updateCompanyMainImageOnTheRight($input: UpdateCompanyMainImageOnTheRight!) {
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
        $results = (array) $this->call("data.{$selector}", $graphql, $params);
        $item    = reset($results) ?: null;

        return $item;
    }

    /**
     * @param array<mixed>  $params
     * @param array<string> $files
     */
    public function call(string $selector, string $graphql, array $params = [], array $files = []): mixed {
        // Enabled?
        if (!$this->isEnabled()) {
            throw new DataLoaderDisabled();
        }

        // Prepare
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
        $request = $this->client
            ->timeout($timeout)
            ->withHeaders($headers);

        if ($files) {
            $map       = [];
            $index     = 0;
            $variables = $params;

            foreach ($files as $variable) {
                $name       = 'file'.($index++);
                $file       = Arr::get($params, $variable);
                $map[$name] = ["variables.{$variable}"];

                if ($file instanceof SplFileInfo) {
                    $file = Utils::streamFor(Utils::tryFopen($file->getPathname(), 'r'));
                }

                $request->attach($name, $file);

                Arr::set($variables, $variable, null);
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
                    'contents' => json_encode($map),
                ],
            ];
        }

        // Call
        $begin = time();

        try {
            $this->dispatcher->dispatch(new RequestStarted($selector, $graphql, $params));

            $response = $request->post($url, $data);

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

    protected function getCustomerPropertiesGraphQL(): string {
        return <<<'GRAPHQL'
            id
            name
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

    protected function getDistributorPropertiesGraphQL(): string {
        return <<<'GRAPHQL'
            id
            name
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
            dataQualityScore
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
                  {$this->getCustomerPropertiesGraphQL()}
                }

                resellerId

                distributor {
                    {$this->getDistributorPropertiesGraphQL()}
                }
            }

            skuNumber
            skuDescription

            supportPackage
            supportPackageDescription

            warrantyEndDate

            estimatedValueRenewal

            customer {
              {$this->getCustomerPropertiesGraphQL()}
            }

            reseller {
              id
            }

            distributor {
                {$this->getDistributorPropertiesGraphQL()}
            }
            GRAPHQL;
    }
    //</editor-fold>
}
