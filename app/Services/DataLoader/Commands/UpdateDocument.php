<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Commands\Concerns\WithBooleanOptions;
use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Loader;
use App\Services\DataLoader\Loaders\DocumentLoader;
use Illuminate\Contracts\Debug\ExceptionHandler;

use function array_unique;

class UpdateDocument extends Update {
    use WithBooleanOptions;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = 'ep:data-loader-update-document
        {id* : The ID of the document}
        {--c|create : Create document if not exists}
        {--C|no-create : Do not create document if not exists (default)}
    ';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $description = 'Update document(s) with given ID(s).';

    public function handle(ExceptionHandler $handler, Container $container): int {
        $create = $this->getBooleanOption('create', false);
        $ids    = array_unique($this->argument('id'));

        return $this->process($handler, $container, $ids, $create);
    }

    protected function makeLoader(Container $container): Loader {
        return $container->make(DocumentLoader::class);
    }
}
