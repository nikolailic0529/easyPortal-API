<?php declare(strict_types = 1);

namespace App\Http\Middleware;

use App\Services\I18n\CurrentLocale;
use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;

class SetLocale {
    public function __construct(
        protected Application $app,
        protected CurrentLocale $locale,
    ) {
        // empty
    }

    public function handle(Request $request, Closure $next): mixed {
        $this->app->setLocale($this->locale->get());

        return $next($request);
    }
}
