<?php declare(strict_types = 1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use function config;
use function sprintf;

class InviteOrganizationUser extends Mailable {
    use Queueable;
    use SerializesModels;

    public function __construct(protected string $url) {
        // empty
    }

    public function build(): Mailable {
        return $this
            ->subject(sprintf('You have been invited to join %s', config('app.name')))
            ->markdown('invite_user', [
                'url' => $this->url,
            ]);
    }
}
