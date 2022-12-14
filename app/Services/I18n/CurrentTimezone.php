<?php declare(strict_types = 1);

namespace App\Services\I18n;

use App\Services\Auth\Auth;
use App\Services\I18n\Contracts\HasTimezonePreference;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Session\Session;

use function is_string;

class CurrentTimezone {
    public function __construct(
        protected Repository $config,
        protected Session $session,
        protected Auth $auth,
        protected CurrentOrganization $organization,
    ) {
        // empty
    }

    public function get(): string {
        // Session.timezone
        $session = $this->session->get('timezone');

        if ($session && is_string($session)) {
            return $session;
        }

        // User.timezone
        $user = $this->auth->getUser();

        if ($user instanceof HasTimezonePreference) {
            $preferred = $user->preferredTimezone();

            if ($preferred) {
                return $preferred;
            }
        }

        // Organization.timezone
        if ($this->organization->defined()) {
            $preferred = $this->organization->preferredTimezone();

            if ($preferred) {
                return $preferred;
            }
        }

        // Default
        return $this->config->get('app.timezone') ?: 'UTC';
    }

    public function set(string $timezone): void {
        // We must not set the `app.timezone` settings because it will lead to
        // invalid dates in database queries.

        $this->session->put('timezone', $timezone);
    }
}
