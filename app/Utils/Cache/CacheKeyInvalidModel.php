<?php declare(strict_types = 1);

namespace App\Utils\Cache;

use App\Exceptions\ApplicationException;
use Illuminate\Database\Eloquent\Model;
use Throwable;

use function sprintf;

class CacheKeyInvalidModel extends ApplicationException {
    public function __construct(Model $model, Throwable $previous = null) {
        parent::__construct(sprintf(
            'The instance of `%s` model should exist and have a non-empty key.',
            $model::class,
        ), $previous);

        $this->setContext([
            'model' => $model,
        ]);
    }
}
