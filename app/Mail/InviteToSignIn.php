<?php declare(strict_types = 1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use function config;
use function sprintf;

class InviteToSignIn extends Mailable {
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(protected string $url) {
        // empty
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build() {
        return $this
            ->subject(sprintf('You have been invited to join %s', config('app.name')))
            ->markdown('invite_user', [
                'url' => $this->url,
            ]);
    }
}
