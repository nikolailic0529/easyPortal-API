<?php declare(strict_types = 1);

namespace App\Services\I18n;

use App\Services\Organization\CurrentOrganization;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Session\Session;
use Illuminate\Contracts\Translation\HasLocalePreference;

class Locale {
    public function __construct(
        protected AuthManager $auth,
        protected Application $app,
        protected Session $session,
        protected CurrentOrganization $organization,
    ) {
        // empty
    }

    public function get(): string {
        // Session.locale
        if ($this->session->has('locale')) {
            return $this->session->get('locale');
        }

        // User.locale
        $user = $this->auth->guard()->user();
        if ($user instanceof HasLocalePreference && $user->preferredLocale()) {
            return $user->preferredLocale();
        }

        // Organization.locale
        if ($this->organization->defined() && $this->organization->preferredLocale()) {
            return $this->organization->preferredLocale();
        }

        // Default
        return $this->app->getLocale();
    }

    public function set(string $locale): void {
        $this->session->put('locale', $locale);
        $this->app->setLocale($locale);
    }
}