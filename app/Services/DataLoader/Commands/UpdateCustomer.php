<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Loader\Loader;
use App\Services\DataLoader\Loader\Loaders\CustomerLoader;
use App\Utils\Console\WithBooleanOptions;
use Illuminate\Contracts\Debug\ExceptionHandler;

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
        {--warranty-check : run warranty check before update}
        {--no-warranty-check : do not run warranty check before update (default)}
        {--c|create : Create customer if not exists (default)}
        {--C|no-create : Do not create customer if not exists}
        {--a|assets : Load assets}
        {--A|no-assets : Skip assets (default)}
        {--d|documents : Load documents}
        {--D|no-documents : Skip documents (default)}
        {--assets-documents : Load assets documents to calculate extended warranties, required --a|assets (default)}
        {--no-assets-documents : Skip assets documents}
    ';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $description = 'Update customer(s) with given ID(s).';

    public function handle(ExceptionHandler $handler, Container $container): int {
        $create = $this->getBooleanOption('create', true);
        $ids    = array_unique((array) $this->argument('id'));

        return $this->process($handler, $container, $ids, $create);
    }

    protected function makeLoader(Container $container): Loader {
        return $container->make(CustomerLoader::class)
            ->setWithWarrantyCheck($this->getBooleanOption('warranty-check', false))
            ->setWithAssets($this->getBooleanOption('assets', false))
            ->setWithAssetsDocuments($this->getBooleanOption('assets-documents', true))
            ->setWithDocuments($this->getBooleanOption('documents', false));
    }
}
