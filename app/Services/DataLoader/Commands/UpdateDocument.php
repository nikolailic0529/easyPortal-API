<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Loader\Loader;
use App\Services\DataLoader\Loader\Loaders\DocumentLoader;
use App\Utils\Console\WithOptions;
use Illuminate\Contracts\Debug\ExceptionHandler;

use function array_unique;

class UpdateDocument extends Update {
    use WithOptions;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = 'ep:data-loader-update-document
        {id* : The ID of the document}
    ';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $description = 'Update document(s) with given ID(s).';

    public function handle(ExceptionHandler $handler, Container $container): int {
        $ids = array_unique((array) $this->argument('id'));

        return $this->process($handler, $container, $ids);
    }

    protected function makeLoader(Container $container): Loader {
        return $container->make(DocumentLoader::class);
    }
}
