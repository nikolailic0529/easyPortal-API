<?php declare(strict_types = 1);

namespace App\Mail;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use function app;
use function rtrim;

class InviteOrganizationUser extends Mailable {
    use Queueable;
    use SerializesModels;

    public function __construct(protected Organization $organization) {
        // empty
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build() {
        $config = app()->make(Repository::class);
        $base   = (string) $config->get('ep.dashboard_url');
        $base   = rtrim($base, '/');
        $url    = "{$base}/{$this->organization->getKey()}";
        return $this->markdown('invite_user', [
            'url' => $url,
        ]);
    }
}
