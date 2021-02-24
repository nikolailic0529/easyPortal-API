<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizers;

use Illuminate\Contracts\Queue\QueueableEntity;

use function is_array;
use function is_null;
use function is_scalar;
use function is_string;
use function ksort;
use function mb_strtolower;

class KeyNormalizer implements Normalizer {
    protected StringNormalizer $normalizer;

    public function __construct() {
        $this->normalizer = new StringNormalizer();
    }

    public function normalize(mixed $value): mixed {
        $value = $this->prepare($value);

        return $value;
    }

    protected function prepare(mixed $value): mixed {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->prepare($v);
            }

            ksort($value);
        } elseif ($value instanceof QueueableEntity) {
            $value = [
                $value::class,
                $value->getQueueableConnection(),
                $value->getQueueableId(),
            ];
        } elseif ((is_null($value) || is_scalar($value)) && !is_string($value)) {
            // no action
        } else {
            $value = $this->normalizer->normalize($value);
            $value = mb_strtolower($value);
        }

        return $value;
    }
}
