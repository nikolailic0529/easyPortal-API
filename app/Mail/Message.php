<?php declare(strict_types = 1);

namespace App\Mail;

use App\GraphQL\Objects\MessageInput as MessageObject;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Message extends Mailable {
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected ?User $user,
        protected MessageObject $message,
    ) {
        // empty
    }

    public function build(Repository $config): void {
        $this
            ->subject($this->message->subject)
            ->to($config->get('ep.email_address'))
            ->cc((array) $this->message->cc)
            ->bcc((array) $this->message->bcc)
            ->markdown('message', [
                'from'    => $this->user->email ?? 'guest',
                'message' => $this->message->message,
            ]);

        foreach ($this->message->files as $file) {
            $this->attach($file->getPathname(), [
                'as'   => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
            ]);
        }
    }
}
