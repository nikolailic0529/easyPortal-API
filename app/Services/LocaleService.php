<?php declare(strict_types = 1);

namespace App\Services;

use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Session\Session;
use Illuminate\Contracts\Translation\HasLocalePreference;

class LocaleService {
    protected AuthManager $auth;
    protected Application $app;
    protected Session $session;

    public function __construct(AuthManager $auth, Application $app, Session $session) {
        $this->auth    = $auth;
        $this->app     = $app;
        $this->session = $session;
    }

    public function get(): string {
        // Session.locale
        if ($this->session->has('locale')) {
            return $this->session->get('locale');
        }
        // User.locale
        $user = $this->auth->user();
        if ($user instanceof HasLocalePreference && $user->preferredLocale()) {
            return $user->preferredLocale();
        }
        // Default
        return $this->app->getLocale();
    }

    public function set(string $locale): void {
        $this->session->put('locale', $locale);
        $this->app->setLocale($locale);
    }
}
