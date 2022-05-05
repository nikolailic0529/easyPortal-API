<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Loader\Loader;
use App\Services\DataLoader\Loader\Loaders\AssetLoader;
use App\Utils\Console\WithOptions;
use Illuminate\Contracts\Debug\ExceptionHandler;

use function array_unique;

class UpdateAsset extends Update {
    use WithOptions;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = 'ep:data-loader-update-asset
        {id* : The ID of the asset}
        {--warranty-check : run warranty check before update}
        {--no-warranty-check : do not run warranty check before update (default)}
        {--d|documents : Load asset documents (and warranties) (default)}
        {--D|no-documents : Skip asset documents}
    ';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $description = 'Update asset(s) with given ID(s).';

    public function handle(ExceptionHandler $handler, Container $container): int {
        $ids = array_unique((array) $this->argument('id'));

        return $this->process($handler, $container, $ids);
    }

    protected function makeLoader(Container $container): Loader {
        return $container->make(AssetLoader::class)
            ->setWithWarrantyCheck((bool) $this->getBoolOption('warranty-check', false))
            ->setWithDocuments((bool) $this->getBoolOption('documents', true));
    }
}
