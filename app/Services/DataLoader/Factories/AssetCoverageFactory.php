<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\AssetCoverage;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\AssetCoverageResolver;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAsset;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

use function implode;
use function sprintf;

class AssetCoverageFactory extends ModelFactory {
    public function __construct(
        LoggerInterface $logger,
        Normalizer $normalizer,
        protected AssetCoverageResolver $assetCoverages,
    ) {
        parent::__construct($logger, $normalizer);
    }

    public function find(Type $type): ?AssetCoverage {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return parent::find($type);
    }

    public function create(Type $type): ?AssetCoverage {
        $model = null;

        if ($type instanceof ViewAsset) {
            $model = $this->createFromAsset($type);
        } else {
            throw new InvalidArgumentException(sprintf(
                'The `$type` must be instance of `%s`.',
                implode('`, `', [
                    ViewAsset::class,
                ]),
            ));
        }

        return $model;
    }

    protected function createFromAsset(ViewAsset $asset): ?AssetCoverage {
        return $this->assetCoverage($asset->assetCoverage);
    }

    protected function assetCoverage(?string $key): ?AssetCoverage {
        $assetCoverage = null;

        if ($key) {
            $assetCoverage = $this->assetCoverages->get(
                $key,
                $this->factory(function () use ($key): AssetCoverage {
                    $model = new AssetCoverage();

                    $model->key  = $this->normalizer->string($key);
                    $model->name = $this->normalizer->string($key);

                    $model->save();

                    return $model;
                }),
            );
        }

        return $assetCoverage;
    }
}
