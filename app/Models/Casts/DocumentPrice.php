<?php declare(strict_types = 1);

namespace App\Models\Casts;

use App\Models\Document;
use App\Models\DocumentEntry;
use App\Utils\Eloquent\Callbacks\GetKey;
use Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes;
use Illuminate\Support\Facades\Config;
use LogicException;

use function array_intersect;
use function is_numeric;
use function number_format;
use function sprintf;

class DocumentPrice implements CastsInboundAttributes {
    /**
     * @inheritDoc
     */
    public function set($model, string $key, $value, array $attributes): mixed {
        // Null?
        if ($value === null) {
            return null;
        }

        // Document?
        $document = null;

        if ($model instanceof DocumentEntry) {
            $document = $model->document;
        } elseif ($model instanceof Document) {
            $document = $model;
        } else {
            throw new LogicException(sprintf(
                'The `%s` class is not supported.',
                $model::class,
            ));
        }

        if ($document === null) {
            return null;
        }

        // Hidden?
        $statuses = (array) Config::get('ep.document_statuses_no_price');
        $actual   = $document->statuses->map(new GetKey())->all();

        if (array_intersect($statuses, $actual)) {
            return null;
        }

        // Numeric?
        if (!is_numeric($value)) {
            throw new LogicException('The `$value` is not numeric.');
        }

        return number_format((float) $value, 2, '.', '');
    }
}
