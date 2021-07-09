<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Coverage;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\CoverageResolver;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAsset;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

use function implode;
use function sprintf;

class CoverageFactory extends ModelFactory {
    public function __construct(
        LoggerInterface $logger,
        Normalizer $normalizer,
        protected CoverageResolver $coverages,
    ) {
        parent::__construct($logger, $normalizer);
    }

    public function find(Type $type): ?Coverage {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return parent::find($type);
    }

    public function create(Type $type): ?Coverage {
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

    protected function createFromAsset(ViewAsset $asset): ?Coverage {
        return $this->assetCoverage($asset->assetCoverage);
    }

    protected function assetCoverage(?string $key): ?Coverage {
        $assetCoverage = null;

        if ($key) {
            $assetCoverage = $this->coverages->get(
                $key,
                $this->factory(function () use ($key): Coverage {
                    $model = new Coverage();

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
