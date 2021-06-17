<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Commands\Concerns\WithBooleanOptions;
use App\Services\DataLoader\DataLoaderService;
use Psr\Log\LoggerInterface;

use function array_unique;

class LoadAsset extends Load {
    use WithBooleanOptions;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = 'ep:data-loader-load-asset
        {id* : The ID of the asset}
        {--c|create : Create asset if not exists}
        {--C|no-create : Do not create asset if not exists (default)}
        {--d|documents : Load asset documents (and warranties) (default)}
        {--D|no-documents : Skip asset documents}
    ';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $description = 'Update asset(s) with given ID(s).';

    public function handle(DataLoaderService $service, LoggerInterface $logger): int {
        $loader = $service->getAssetLoader();
        $create = $this->getBooleanOption('create', false);
        $ids    = array_unique($this->argument('id'));

        $loader->setWithDocuments($this->getBooleanOption('documents', true));

        return $this->process($logger, $loader, $ids, $create);
    }
}
