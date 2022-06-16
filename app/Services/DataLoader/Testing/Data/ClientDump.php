<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data;

use App\Services\DataLoader\Schema\CentralAssetDbStatistics;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\Document;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Utils\JsonObject\JsonObject;
use App\Utils\JsonObject\JsonObjectIterator;
use Exception;
use Generator;
use Illuminate\Support\Arr;

use function array_is_list;
use function array_slice;
use function explode;
use function implode;
use function is_array;
use function sprintf;

class ClientDump extends JsonObject {
    public string $selector;
    public string $graphql;

    /**
     * @var array<string, mixed>
     */
    public array $variables;

    /**
     * @var array<string, mixed>
     */
    public array $response;

    /**
     * @return Generator<JsonObject>
     */
    public function getResponseIterator(bool $save = false): Generator {
        /** @var array<string, class-string<JsonObject>> $selectors */
        $selectors = [
            'data.getAssets'                   => ViewAsset::class,
            'data.getCompanyById'              => Company::class,
            'data.getCentralAssetDbStatistics' => CentralAssetDbStatistics::class,
            'data.getDistributors'             => Company::class,
            'data.getResellers'                => Company::class,
            'data.getCustomers'                => Company::class,
            'data.getAssetsByCustomerId'       => ViewAsset::class,
            'data.getAssetsByResellerId'       => ViewAsset::class,
            'data.getDocumentById'             => Document::class,
            'data.getDocuments'                => Document::class,
            'data.getDocumentsByReseller'      => Document::class,
            'data.getDocumentsByCustomer'      => Document::class,
        ];
        $selector  = implode('.', array_slice(explode('.', $this->selector), 0, 2));
        $class     = $selectors[$selector] ?? null;

        if (!$class) {
            throw new Exception(sprintf(
                'Unknown selector: `%s`.',
                $selector,
            ));
        }

        $data = Arr::get($this->response, $selector);
        $data = is_array($data)
            ? (array_is_list($data) ? $class::make($data) : [new $class($data)])
            : null;

        if ($data !== null) {
            yield from new JsonObjectIterator($data);
        } else {
            yield from [];
        }

        if ($save) {
            Arr::set($this->response, $selector, $data);
        }

        return $data;
    }
}
