<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Language;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\LanguageResolver;
use App\Services\DataLoader\Schema\AssetDocument;
use App\Services\DataLoader\Schema\Document;
use App\Services\DataLoader\Schema\Type;
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
        } elseif ($type instanceof AssetDocument) {
            $model = $this->createFromAssetDocument($type);
        } elseif ($type instanceof Document) {
            $model = $this->createFromDocument($type);
        } else {
            throw new InvalidArgumentException(sprintf(
                'The `$type` must be instance of `%s`.',
                implode('`, `', [
                    AssetDocumentObject::class,
                    AssetDocument::class,
                    Document::class,
                ]),
            ));
        }

        return $model;
    }

    protected function createFromAssetDocumentObject(AssetDocumentObject $document): ?Language {
        return $this->createFromAssetDocument($document->document);
    }

    protected function createFromAssetDocument(AssetDocument $document): ?Language {
        return isset($document->document)
            ? $this->createFromDocument($document->document)
            : $this->language($document->languageCode);
    }

    protected function createFromDocument(Document $document): ?Language {
        return $this->language($document->languageCode);
    }

    protected function language(?string $code): ?Language {
        $language = null;

        if ($code) {
            $language = $this->languages->get(
                $code,
                $this->factory(function () use ($code): Language {
                    $model = new Language();

                    $model->code = $this->normalizer->string($code);
                    $model->name = $this->normalizer->string($code);

                    $model->save();

                    return $model;
                }),
            );
        }

        return $language;
    }
}
