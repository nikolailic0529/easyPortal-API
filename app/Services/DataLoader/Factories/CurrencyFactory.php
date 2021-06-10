<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Currency;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\CurrencyResolver;
use App\Services\DataLoader\Schema\DocumentEntry;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAssetDocument;
use App\Services\DataLoader\Schema\ViewDocument;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

use function implode;
use function sprintf;

class CurrencyFactory extends ModelFactory {
    public function __construct(
        LoggerInterface $logger,
        Normalizer $normalizer,
        protected CurrencyResolver $currencies,
    ) {
        parent::__construct($logger, $normalizer);
    }

    public function find(Type $type): ?Currency {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return parent::find($type);
    }

    public function create(Type $type): ?Currency {
        $model = null;

        if ($type instanceof AssetDocumentObject) {
            $model = $this->createFromAssetDocumentObject($type);
        } elseif ($type instanceof ViewAssetDocument) {
            $model = $this->createFromAssetDocument($type);
        } elseif ($type instanceof ViewDocument) {
            $model = $this->createFromDocument($type);
        } elseif ($type instanceof DocumentEntry) {
            $model = $this->createFromDocumentEntry($type);
        } else {
            throw new InvalidArgumentException(sprintf(
                'The `$type` must be instance of `%s`.',
                implode('`, `', [
                    AssetDocumentObject::class,
                    ViewAssetDocument::class,
                    ViewDocument::class,
                    DocumentEntry::class,
                ]),
            ));
        }

        return $model;
    }

    protected function createFromAssetDocumentObject(AssetDocumentObject $document): ?Currency {
        $currency = null;

        if (isset($document->document->document)) {
            $currency = $this->createFromDocument($document->document->document);
        }

        if (!$currency) {
            $currency = $this->createFromAssetDocument($document->document);
        }

        return $currency;
    }

    protected function createFromAssetDocument(ViewAssetDocument $document): ?Currency {
        return $this->currency($document->currencyCode);
    }

    protected function createFromDocument(ViewDocument $document): ?Currency {
        return $this->currency($document->currencyCode);
    }

    protected function createFromDocumentEntry(DocumentEntry $entry): ?Currency {
        return $this->currency($entry->currencyCode);
    }

    protected function currency(?string $code): ?Currency {
        $currency = null;

        if ($code) {
            $currency = $this->currencies->get(
                $code,
                $this->factory(function () use ($code): Currency {
                    $model = new Currency();

                    $model->code = $this->normalizer->string($code);
                    $model->name = $this->normalizer->string($code);

                    $model->save();

                    return $model;
                }),
            );
        }

        return $currency;
    }
}
