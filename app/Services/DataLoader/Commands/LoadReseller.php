<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Commands\Concerns\WithBooleanOptions;
use App\Services\DataLoader\DataLoaderService;
use Psr\Log\LoggerInterface;

use function array_unique;

class LoadReseller extends Load {
    use WithBooleanOptions;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = 'ep:data-loader-load-reseller
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

    public function handle(DataLoaderService $service, LoggerInterface $logger): int {
        $loader = $service->getResellerLoader();
        $create = $this->getBooleanOption('create', false);
        $ids    = array_unique($this->argument('id'));

        $loader->setWithAssets($this->getBooleanOption('assets', false));
        $loader->setWithAssetsDocuments($this->getBooleanOption('assets-documents', true));

        return $this->process($logger, $loader, $ids, $create);
    }
}
