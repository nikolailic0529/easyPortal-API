<?php declare(strict_types = 1);

namespace App\GraphQL\Scalars;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Nuwave\Lighthouse\Schema\Types\Scalars\Date as LighthouseDate;

/**
 * ISO 8601 Date string with format Y-m-d (`2011-05-23`).
 */
class Date extends LighthouseDate {
    /**
     * @inheritdoc
     */
    protected function parse($value): Carbon {
        // Parent method doesn't support DateTimeInterface
        if ($value instanceof DateTimeInterface) {
            $value = new Carbon($value);
        } else {
            $value = parent::parse($value);
        }

        // Return
        return $value;
    }
}
