<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Concerns;

use App\Utils\Eloquent\Callbacks\SetKey;
use App\Utils\Eloquent\Model;
use App\Utils\Eloquent\Pivot;
use LogicException;

use function assert;
use function is_string;

/**
 * Marks the key is a string.
 *
 * Also required:
 *
 *      protected $keyType   = 'string';
 *      public $incrementing = false;
 *
 * @mixin Model
 * @mixin Pivot
 */
trait StringKey {
    public function hasKey(): bool {
        return parent::getKey() !== null;
    }

    public function getKey(): string {
        $key = parent::getKey();

        if ($key !== null && !is_string($key)) {
            throw new LogicException('Model key must be a UUID.');
        }

        if ($key === null && !$this->exists) {
            (new SetKey())($this);

            $key = parent::getKey();

            assert(is_string($key), '(phpstan) SetKey use random UUID as a key => key must be a string here.');
        }

        if ($key === null) {
            throw new LogicException('Model key is `null`.');
        }

        return $key;
    }

    public function setKey(string $key): static {
        if (parent::getKey() === null) {
            $this->setAttribute($this->getKeyName(), $key);
        } else {
            throw new LogicException('Model key cannot be changed.');
        }

        return $this;
    }
}
