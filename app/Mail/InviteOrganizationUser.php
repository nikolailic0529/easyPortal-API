<?php declare(strict_types = 1);

namespace App\Mail;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Routing\UrlGenerator;
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
        $url = app()->make(UrlGenerator::class);
        return $this->markdown('invite_user', [
            'url' => $url->to("auth/organizations/{$this->organization->getKey()}")
        ]);
    }
}
