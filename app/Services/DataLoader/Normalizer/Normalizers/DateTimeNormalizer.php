<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizer\Normalizers;

use App\Utils\JsonObject\Normalizer;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\Date;

use function config;
use function is_int;
use function is_string;
use function preg_match;

class DateTimeNormalizer implements Normalizer {
    public static function normalize(mixed $value): ?CarbonImmutable {
        // Parse
        if ($value) {
            if (is_int($value) || (is_string($value) && preg_match('/^\d+$/', $value))) {
                $value = Date::createFromTimestampMs($value);
            } elseif (is_string($value)) {
                if (preg_match('|^\d{4}-\d{2}-\d{2}$|', $value)) {
                    $value = (Date::createFromFormat('Y-m-d', $value, 'UTC') ?: null)?->startOfDay();
                } elseif (preg_match('|^\d{2}/\d{2}/\d{4}$|', $value)) {
                    $value = (Date::createFromFormat('d/m/Y', $value, 'UTC') ?: null)?->startOfDay();
                } elseif (preg_match('|^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+\d{2}:\d{2}$|', $value)) {
                    $value = Date::createFromFormat(DateTimeInterface::ATOM, $value);
                } else {
                    $value = null;
                }
            } else {
                $value = null;
            }
        } else {
            $value = null;
        }

        // Set Timezone
        if ($value instanceof DateTimeInterface && !($value instanceof CarbonImmutable)) {
            $value = Carbon::make($value)?->toImmutable();
        }

        if ($value instanceof CarbonImmutable) {
            $tz    = config('app.timezone') ?: 'UTC';
            $value = $value->setTimezone($tz);
        } else {
            $value = null;
        }

        return $value;
    }
}
