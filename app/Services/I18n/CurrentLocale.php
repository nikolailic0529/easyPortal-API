<?php declare(strict_types = 1);

namespace App\Services\I18n;

use App\Services\Auth\Auth;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Session\Session;
use Illuminate\Contracts\Translation\HasLocalePreference;

use function is_string;

class CurrentLocale {
    public function __construct(
        protected Auth $auth,
        protected Session $session,
        protected Application $app,
        protected CurrentOrganization $organization,
    ) {
        // empty
    }

    public function get(): string {
        // Session.locale
        $session = $this->session->get('locale');

        if ($session && is_string($session)) {
            return $session;
        }

        // User.locale
        $user = $this->auth->getUser();

        if ($user instanceof HasLocalePreference) {
            $preferred = $user->preferredLocale();

            if ($preferred) {
                return $preferred;
            }
        }

        // Organization.locale
        if ($this->organization->defined()) {
            $preferred = $this->organization->preferredLocale();

            if ($preferred) {
                return $preferred;
            }
        }

        // Default
        return $this->app->getLocale();
    }

    public function set(string $locale): void {
        $this->session->put('locale', $locale);
        $this->app->setLocale($locale);
    }
}
