<?php declare(strict_types = 1);

namespace App\Mail\Concerns;

use App\Models\ChangeRequest;
use App\Models\OrganizationUser;
use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Arr;

use function array_filter;
use function array_merge;
use function array_unique;

trait DefaultRecipients {
    /**
     * @param array<string>|string|null ...$recipients
     *
     * @return array<string>
     */
    public function getDefaultRecipients(
        Repository $config,
        QuoteRequest|ChangeRequest $model,
        array|string|null ...$recipients,
    ): array {
        // User
        $recipients = Arr::flatten(array_filter(
            $recipients,
            static function (array|string|null $recipients): array {
                return (array) $recipients;
            },
        ));

        // Org Admins
        $orgAdminGroup = $config->get('ep.keycloak.org_admin_group');

        if ($orgAdminGroup) {
            $org        = $model->organization;
            $orgAdmins  = $org->users()
                ->where((new OrganizationUser())->qualifyColumn('role_id'), '=', $orgAdminGroup)
                ->with('organizations')
                ->get()
                ->filter(static function (User $user) use ($org): bool {
                    return $user->isEnabled($org);
                })
                ->map(static function (User $user): string {
                    return $user->email;
                })
                ->all();
            $recipients = array_merge($recipients, $orgAdmins);
        }

        // Cleanup
        $recipients = array_unique(array_filter($recipients));

        // Return
        return $recipients;
    }
}
