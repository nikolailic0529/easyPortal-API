<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Message;

use App\GraphQL\Objects\Message;
use App\Mail\Message as MessageMail;
use App\Services\Auth\Auth;
use Illuminate\Contracts\Mail\Mailer;

use function array_filter;
use function array_unique;

class Create {
    public function __construct(
        protected Auth $auth,
        protected Mailer $mailer,
    ) {
        // empty
    }

    /**
     * @param array{input: array<string, mixed>} $args
     */
    public function __invoke(mixed $root, array $args): bool {
        $user         = $this->auth->getUser();
        $message      = new Message($args['input']);
        $message->cc  = array_unique(array_filter((array) $message->cc)) ?: null;
        $message->bcc = array_unique(array_filter((array) $message->bcc)) ?: null;
        $mail         = new MessageMail($user, $message);

        $this->mailer->send($mail);

        return true;
    }
}
