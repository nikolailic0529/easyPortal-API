<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders;

use App\Services\DataLoader\Loader;
use App\Services\DataLoader\ResolverFinder;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\Type;
use Illuminate\Database\Eloquent\Model;

use function is_string;

abstract class CompanyLoader extends Loader implements ResolverFinder {
    protected function getObjectById(string $id): ?Type {
        return $this->client->getCompanyById($id);
    }

    /**
     * @inheritDoc
     */
    protected function getObject(array $properties): ?Type {
        return new Company($properties);
    }

    public function find(mixed $key): ?Model {
        return is_string($key) ? $this->create($key) : null;
    }
}
