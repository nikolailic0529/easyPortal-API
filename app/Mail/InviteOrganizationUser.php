<?php declare(strict_types = 1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use function app;
use function strtr;

class InviteOrganizationUser extends Mailable {
    use Queueable;
    use SerializesModels;

    public function __construct(protected string $token) {
        // empty
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build() {
        $generator = app()->make(UrlGenerator::class);
        $config    = app()->make(Repository::class);
        $url       = $generator->to(strtr($config->get('ep.client.invite_complete_uri'), [
            '{token}' => $this->token,
        ]));
        return $this->markdown('invite_user', [
            'url' => $url,
        ]);
    }
}
