<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders;

use App\Models\Model;
use App\Services\DataLoader\Factory;
use App\Services\DataLoader\Loader;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\Type;
use InvalidArgumentException;

use function get_class;
use function sprintf;

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

    protected function process(?Type $object): ?Model {
        // Null?
        if ($object === null) {
            return null;
        }

        // Valid?
        if (!($object instanceof Company)) {
            throw new InvalidArgumentException(sprintf(
                'The `$object` should be instance of `%s`, `%s` given.',
                Company::class,
                $object::class,
            ));
        }

        // Process
        $company = $this->getCompanyFactory()->create($object);

        // Return
        return $company;
    }

    abstract protected function getCompanyFactory(): Factory;
}
