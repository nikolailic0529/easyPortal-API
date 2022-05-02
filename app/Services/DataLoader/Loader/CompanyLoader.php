<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader;

use App\Models\Asset;
use App\Models\Document;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Collector\Collector;
use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Factory\Factories\CustomerFactory;
use App\Services\DataLoader\Factory\Factories\ResellerFactory;
use App\Services\DataLoader\Loader\Concerns\WithAssets;
use App\Services\DataLoader\Loader\Concerns\WithDocuments;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;
use DateTimeInterface;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TOwner of \App\Models\Reseller|\App\Models\Customer
 */
abstract class CompanyLoader extends Loader {
    /**
     * @phpstan-use WithAssets<TOwner>
     */
    use WithAssets;

    /**
     * @phpstan-use WithDocuments<TOwner>
     */
    use WithDocuments;

    public function __construct(
        Container $container,
        ExceptionHandler $exceptionHandler,
        Dispatcher $dispatcher,
        Client $client,
        Collector $collector,
        protected ResellerFactory $resellerFactory,
        protected CustomerFactory $customerFactory,
    ) {
        parent::__construct($container, $exceptionHandler, $dispatcher, $client, $collector);
    }

    //<editor-fold desc="Getters / Setters">
    // =========================================================================
    protected function getResellersFactory(): ResellerFactory {
        return $this->resellerFactory;
    }

    protected function getCustomersFactory(): CustomerFactory {
        return $this->customerFactory;
    }
    // </editor-fold>

    // <editor-fold desc="API">
    // =========================================================================
    protected function process(?Type $object): ?Model {
        // Process
        $company = parent::process($object);

        if ($this->isWithAssets() && $company) {
            $this->loadAssets($company);
        }

        if ($this->isWithDocuments() && $company) {
            $this->loadDocuments($company);
        }

        // Return
        return $company;
    }

    /**
     * @inheritDoc
     */
    protected function getObject(array $properties): ?Type {
        return new Company($properties);
    }
    // </editor-fold>

    // <editor-fold desc="WithAssets">
    // =========================================================================
    /**
     * @param TOwner $owner
     *
     * @return Builder<Asset>
     */
    protected function getMissedAssets(Model $owner, DateTimeInterface $datetime): Builder {
        return $owner->assets()->where('synced_at', '<', $datetime)->getQuery();
    }
    // </editor-fold>

    // <editor-fold desc="WithDocuments">
    // =========================================================================
    /**
     * @param TOwner $owner
     *
     * @return Builder<Document>
     */
    protected function getMissedDocuments(Model $owner, DateTimeInterface $datetime): Builder {
        return $owner->documents()->where('synced_at', '<', $datetime)->getQuery();
    }
    // </editor-fold>
}
