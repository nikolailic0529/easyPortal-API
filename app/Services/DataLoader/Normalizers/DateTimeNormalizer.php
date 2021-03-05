<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizers;

use DateTimeInterface;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Date;

use function is_int;
use function is_string;
use function preg_match;

/**
 * @internal
 */
class DateTimeNormalizer implements Normalizer {
    public function __construct(
        protected Repository $config,
    ) {
        // empty
    }

    public function normalize(mixed $value): ?DateTimeInterface {
        // Parse
        if ($value) {
            if (is_int($value) || (is_string($value) && preg_match('/\d+/', $value))) {
                $value = Date::createFromTimestampMs($value);
            }
        } else {
            $value = null;
        }

        // Set Timezone
        if ($value instanceof DateTimeInterface) {
            $tz    = $this->config->get('app.timezone') ?: 'UTC';
            $value = $value->setTimezone($tz);
        }

        return $value;
    }
}
