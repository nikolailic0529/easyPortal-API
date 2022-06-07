<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Callbacks;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use LogicException;

use function gettype;
use function is_int;
use function is_string;
use function sprintf;

class GetKey {
    public function __invoke(Model|Pivot $model): string|int {
        $key = $model->getKey();

        if (!is_string($key) && !is_int($key)) {
            throw new LogicException(sprintf(
                'Model key should be `string` or `int`, `%s` given.',
                gettype($key),
            ));
        }

        return $key;
    }
}
