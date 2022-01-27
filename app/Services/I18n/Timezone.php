<?php declare(strict_types = 1);

namespace App\Services\I18n;

use App\Services\Auth\Auth;
use App\Services\I18n\Contracts\HasTimezonePreference;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Session\Session;

class Timezone {
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
        if ($this->session->has('timezone')) {
            return $this->session->get('timezone');
        }

        // User.timezone
        $user = $this->auth->getUser();

        if ($user instanceof HasTimezonePreference && $user->preferredTimezone()) {
            return $user->preferredTimezone();
        }

        // Organization.timezone
        if ($this->organization->defined() && $this->organization->preferredTimezone()) {
            return $this->organization->preferredTimezone();
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
