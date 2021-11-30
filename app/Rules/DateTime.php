<?php declare(strict_types = 1);

namespace App\Rules;

use DateTimeInterface;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Concerns\ValidatesAttributes;

use function __;
use function config;
use function is_string;

/**
 * ISO 8601 Date Time string with format `Y-m-dTH:i:sP` (`2018-05-23T13:43:32+00:00`).
 */
class DateTime implements Rule {
    use ValidatesAttributes;

    protected const FORMAT = DateTimeInterface::ATOM;

    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return $this->validateDateFormat($attribute, $value, [
            static::FORMAT,
        ]);
    }

    public function message(): string {
        return __('validation.date_format', [
            'format' => static::FORMAT,
        ]);
    }

    public function parse(mixed $value): DateTimeInterface {
        // Parse
        $datetime = is_string($value)
            ? Date::createFromFormat(static::FORMAT, $value)
            : Date::make($value);

        // Set Timezone
        $tz = config('app.timezone') ?: 'UTC';

        if ($datetime && $tz) {
            $datetime = $datetime->setTimezone($tz);
        }

        // Return
        return $datetime;
    }
}
