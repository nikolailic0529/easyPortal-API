<?php declare(strict_types = 1);

namespace App\GraphQL\Scalars;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Nuwave\Lighthouse\Schema\Types\Scalars\DateTimeTz as LighthouseDateTimeTz;

use function app;

/**
 * ISO 8601 Date Time string with format `Y-m-dTH:i:sP` (`2018-05-23T13:43:32+00:00`).
 */
class DateTime extends LighthouseDateTimeTz {
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

        // Set Timezone
        $tz = app('config')->get('app.timezone') ?: 'UTC';

        if ($value && $tz) {
            $value = $value->setTimezone($tz);
        }

        // Return
        return $value;
    }
}
