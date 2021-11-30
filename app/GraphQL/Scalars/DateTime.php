<?php declare(strict_types = 1);

namespace App\GraphQL\Scalars;

use App\Rules\DateTime as DateTimeRule;
use Illuminate\Support\Carbon;
use Nuwave\Lighthouse\Schema\Types\Scalars\DateTimeTz as LighthouseDateTimeTz;

/**
 * ISO 8601 Date Time string with format `Y-m-dTH:i:sP` (`2018-05-23T13:43:32+00:00`).
 */
class DateTime extends LighthouseDateTimeTz {
    /**
     * @inheritdoc
     */
    protected function parse($value): Carbon {
        $datetime = (new DateTimeRule())->parse($value);
        $datetime = Carbon::make($datetime);

        return $datetime;
    }
}
