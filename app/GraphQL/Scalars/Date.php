<?php declare(strict_types = 1);

namespace App\GraphQL\Scalars;

use Nuwave\Lighthouse\Schema\Types\Scalars\Date as LighthouseDate;

/**
 * ISO 8601 Date string with format Y-m-d (`2011-05-23`).
 */
class Date extends LighthouseDate {
    // empty
}
