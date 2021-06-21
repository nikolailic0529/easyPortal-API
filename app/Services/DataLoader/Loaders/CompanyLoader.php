<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders;

use App\Services\DataLoader\Loader;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\Type;

abstract class CompanyLoader extends Loader {
    protected function getObjectById(string $id): ?Type {
        return $this->client->getCompanyById($id);
    }

    /**
     * @inheritDoc
     */
    protected function getObject(array $properties): ?Type {
        return new Company($properties);
    }
}
