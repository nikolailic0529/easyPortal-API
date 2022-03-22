<?php declare(strict_types = 1);

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware {
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<int, string>
     */
    protected $except = [];
}
