<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Commands\Concerns\WithBooleanOptions;
use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Loader;
use App\Services\DataLoader\Loaders\ResellerLoader;
use Psr\Log\LoggerInterface;

use function array_unique;

class UpdateReseller extends Update {
    use WithBooleanOptions;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = 'ep:data-loader-update-reseller
        {id* : The ID of the reseller}
        {--c|create : Create reseller if not exists}
        {--C|no-create : Do not create reseller if not exists (default)}
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
    protected $description = 'Update reseller(s) with given ID(s).';

    public function handle(LoggerInterface $logger, Container $container): int {
        $create = $this->getBooleanOption('create', false);
        $ids    = array_unique($this->argument('id'));

        return $this->process($logger, $container, $ids, $create);
    }

    protected function makeLoader(Container $container): Loader {
        return $container->make(ResellerLoader::class)
            ->setWithAssets($this->getBooleanOption('assets', false))
            ->setWithAssetsDocuments($this->getBooleanOption('assets-documents', true));
    }
}
