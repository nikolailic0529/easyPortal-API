<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Callbacks;

use App\Utils\Cache\CacheKey;
use App\Utils\Eloquent\SmartSave\Upsertable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

use function array_diff;

class GetUniqueKey extends GetKey {
    /**
     * @param array<string> $ignore
     */
    public function __construct(
        protected array $ignore = [],
    ) {
        // empty
    }

    public function __invoke(Model|Pivot $model): string|int {
        $key = parent::__invoke($model);

        if ($model instanceof Upsertable) {
            $unique = array_diff($model::getUniqueKey(), $this->ignore);
            $attrs  = [];

            foreach ($unique as $attribute) {
                $attrs[] = $model->getAttribute($attribute);
            }

            $key = (string) (new CacheKey($attrs));
        }

        return $key;
    }
}
