<?php declare(strict_types = 1);

namespace App\Exceptions;

use App\GraphQL\Mutations\DispatchApplicationServiceFailed;
use App\GraphQL\Mutations\DispatchApplicationServiceNotFoundException;
use App\Services\Filesystem\StorageFileCorrupted;
use App\Services\Filesystem\StorageFileDeleteFailed;
use App\Services\Filesystem\StorageFileSaveFailed;
use App\Services\Settings\Exceptions\SettingsFailedToLoadEnv;
use Throwable;

class ErrorCodes {
    /**
     * @var array<class-string<\Throwable>,string|int>
     */
    protected static array $map = [
        DispatchApplicationServiceNotFoundException::class => 'ERR01',
        DispatchApplicationServiceFailed::class            => 'ERR02',
        StorageFileCorrupted::class                        => 'ERR03',
        StorageFileDeleteFailed::class                     => 'ERR04',
        StorageFileSaveFailed::class                       => 'ERR05',
        SettingsFailedToLoadEnv::class                     => 'ERR06',
    ];

    public static function getCode(Throwable $throwable): string|int {
        return self::$map[$throwable::class] ?? $throwable->getCode();
    }
}
