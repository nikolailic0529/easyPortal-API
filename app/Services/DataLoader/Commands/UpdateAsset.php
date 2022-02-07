<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Loader\Loader;
use App\Services\DataLoader\Loader\Loaders\AssetLoader;
use App\Utils\Console\WithBooleanOptions;
use Illuminate\Contracts\Debug\ExceptionHandler;

use function array_unique;

class UpdateAsset extends Update {
    use WithBooleanOptions;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = 'ep:data-loader-update-asset
        {id* : The ID of the asset}
        {--warranty-check : run warranty check before update}
        {--no-warranty-check : do not run warranty check before update (default)}
        {--c|create : Create asset if not exists (default)}
        {--C|no-create : Do not create asset if not exists}
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
        $create = $this->getBooleanOption('create', true);
        $ids    = array_unique((array) $this->argument('id'));

        return $this->process($handler, $container, $ids, $create);
    }

    protected function makeLoader(Container $container): Loader {
        return $container->make(AssetLoader::class)
            ->setWithWarrantyCheck($this->getBooleanOption('warranty-check', false))
            ->setWithDocuments($this->getBooleanOption('documents', true));
    }
}
