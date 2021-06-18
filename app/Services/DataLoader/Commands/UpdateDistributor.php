<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Commands\Concerns\WithBooleanOptions;
use App\Services\DataLoader\DataLoaderService;
use Psr\Log\LoggerInterface;

use function array_unique;

class UpdateDistributor extends Update {
    use WithBooleanOptions;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = 'ep:data-loader-update-distributor
        {id* : The ID of the distributor}
        {--c|create : Create distributor if not exists}
        {--C|no-create : Do not create distributor if not exists (default)}
    ';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $description = 'Update distributor(s) with given ID(s).';

    public function handle(DataLoaderService $service, LoggerInterface $logger): int {
        $loader = $service->getDistributorLoader();
        $create = $this->getBooleanOption('create', false);
        $ids    = array_unique($this->argument('id'));

        return $this->process($logger, $loader, $ids, $create);
    }
}
