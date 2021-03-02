<?php declare(strict_types = 1);

namespace App\Services\DataLoader;

use App\Services\DataLoader\Schema\Company;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\Factory;

use function var_dump;

class Client {
    protected const CONFIG = 'data-loader';

    public function __construct(
        protected Repository $config,
        protected Factory $client,
    ) {
        // empty
    }

    public function getCompanyById(string $id): ?Company {
        $json    = $this->call(/** @lang GraphQL */
            <<<'G'
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
            G,
            [
                'id' => $id,
            ],
        );
        $company = Company::create($json['data']['getCompanyById'][0]);

        return $company;
    }

    /**
     * @param array<mixed> $params
     *
     * @return array<mixed>|null
     */
    protected function call(string $graphql, array $params = []): ?array {
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
        return $json;
    }

    public function isEnabled(): bool {
        return $this->setting('enabled')
            && $this->setting('endpoint');
    }

    protected function setting(string $name, mixed $default = null): mixed {
        return $this->config->get(static::CONFIG.'.'.$name, $default);
    }
}
