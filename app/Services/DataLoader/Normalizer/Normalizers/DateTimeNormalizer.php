<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizer\Normalizers;

use App\Services\DataLoader\Normalizer\ValueNormalizer;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Date;

use function is_int;
use function is_string;
use function preg_match;

class DateTimeNormalizer implements ValueNormalizer {
    public function __construct(
        protected Repository $config,
    ) {
        // empty
    }

    public function normalize(mixed $value): ?CarbonImmutable {
        // Parse
        if ($value) {
            if (is_int($value) || (is_string($value) && preg_match('/^\d+$/', $value))) {
                $value = Date::createFromTimestampMs($value);
            } elseif (is_string($value)) {
                if (preg_match('|^\d{4}-\d{2}-\d{2}$|', $value)) {
                    $value = Date::createFromFormat('Y-m-d', $value, 'UTC')->startOfDay();
                } elseif (preg_match('|^\d{2}/\d{2}/\d{4}$|', $value)) {
                    $value = Date::createFromFormat('d/m/Y', $value, 'UTC')->startOfDay();
                } else {
                    // empty
                }
            } else {
                // empty
            }
        } else {
            $value = null;
        }

        // Set Timezone
        if ($value instanceof DateTimeInterface) {
            $value = Carbon::make($value)->toImmutable();
        }

        if ($value instanceof CarbonImmutable) {
            $tz    = $this->config->get('app.timezone') ?: 'UTC';
            $value = $value->setTimezone($tz);
        } else {
            $value = null;
        }

        return $value;
    }
}
