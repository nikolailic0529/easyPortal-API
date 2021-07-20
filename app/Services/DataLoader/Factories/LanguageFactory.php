<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Language;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\LanguageResolver;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAssetDocument;
use App\Services\DataLoader\Schema\ViewDocument;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

use function implode;
use function sprintf;

class LanguageFactory extends ModelFactory {
    public function __construct(
        LoggerInterface $logger,
        Normalizer $normalizer,
        protected LanguageResolver $languages,
    ) {
        parent::__construct($logger, $normalizer);
    }

    public function find(Type $type): ?Language {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return parent::find($type);
    }

    public function create(Type $type): ?Language {
        $model = null;

        if ($type instanceof AssetDocumentObject) {
            $model = $this->createFromAssetDocumentObject($type);
        } elseif ($type instanceof ViewAssetDocument) {
            $model = $this->createFromAssetDocument($type);
        } elseif ($type instanceof ViewDocument) {
            $model = $this->createFromDocument($type);
        } else {
            throw new InvalidArgumentException(sprintf(
                'The `$type` must be instance of `%s`.',
                implode('`, `', [
                    AssetDocumentObject::class,
                    ViewAssetDocument::class,
                    ViewDocument::class,
                ]),
            ));
        }

        return $model;
    }

    protected function createFromAssetDocumentObject(AssetDocumentObject $document): ?Language {
        $language = null;

        if (isset($document->document->document)) {
            $language = $this->createFromDocument($document->document->document);
        }

        if (!$language) {
            $language = $this->createFromAssetDocument($document->document);
        }

        return $language;
    }

    protected function createFromAssetDocument(ViewAssetDocument $document): ?Language {
        return $this->language($document->languageCode);
    }

    protected function createFromDocument(ViewDocument $document): ?Language {
        return $this->language($document->languageCode);
    }

    protected function language(?string $code): ?Language {
        $language = null;

        if ($code) {
            $language = $this->languages->get(
                $code,
                $this->factory(function () use ($code): Language {
                    $model       = new Language();
                    $normalizer  = $this->getNormalizer();
                    $model->code = $normalizer->string($code);
                    $model->name = $normalizer->string($code);

                    $model->save();

                    return $model;
                }),
            );
        }

        return $language;
    }
}
