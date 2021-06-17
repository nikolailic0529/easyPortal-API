<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Commands\Concerns\WithBooleanOptions;
use App\Services\DataLoader\DataLoaderService;
use Psr\Log\LoggerInterface;

use function array_unique;

class UpdateCustomer extends Update {
    use WithBooleanOptions;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = 'ep:data-loader-update-customer
        {id* : The ID of the company}
        {--c|create : Create customer if not exists}
        {--C|no-create : Do not create customer if not exists (default)}
        {--a|assets : Load assets}
        {--A|no-assets : Skip assets (default)}
        {--ad|assets-documents : Load assets documents (and warranties), required --a|assets (default)}
        {--AD|no-assets-documents : Skip assets documents}
    ';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $description = 'Update customer(s) with given ID(s).';

    public function handle(DataLoaderService $service, LoggerInterface $logger): int {
        $loader = $service->getCustomerLoader();
        $create = $this->getBooleanOption('create', false);
        $ids    = array_unique($this->argument('id'));

        $loader->setWithAssets($this->getBooleanOption('assets', false));
        $loader->setWithAssetsDocuments($this->getBooleanOption('assets-documents', true));

        return $this->process($logger, $loader, $ids, $create);
    }
}
