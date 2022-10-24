<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Message;

use App\GraphQL\Objects\MessageInput;
use App\Mail\Message;
use App\Mail\RequestChange;
use App\Models\Asset;
use App\Models\ChangeRequest;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Note;
use App\Models\Organization;
use App\Services\Auth\Auth;
use App\Services\Filesystem\ModelDiskFactory;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Mail\Mailer;

use function array_filter;
use function array_unique;
use function assert;

class Create {
    public function __construct(
        protected Auth $auth,
        protected CurrentOrganization $organization,
        protected Mailer $mailer,
        protected Repository $config,
        protected ModelDiskFactory $disks,
    ) {
        // empty
    }

    /**
     * @param array{input: array<string, mixed>} $args
     */
    public function __invoke(mixed $root, array $args): bool {
        return $this->sendMail(new MessageInput($args['input']));
    }

    public function sendMail(MessageInput $message): bool {
        $user         = $this->auth->getUser();
        $message->cc  = array_unique(array_filter((array) $message->cc)) ?: null;
        $message->bcc = array_unique(array_filter((array) $message->bcc)) ?: null;
        $mail         = new Message($user, $message);

        $this->mailer->send($mail);

        return true;
    }

    public function createRequest(
        Organization|Customer|Asset|Document $object,
        MessageInput $input,
    ): ChangeRequest {
        // User
        $user = $this->auth->getUser();

        assert($user !== null);

        // Create
        $request               = new ChangeRequest();
        $request->user         = $user;
        $request->organization = $this->organization->get();
        $request->object       = $object;
        $request->subject      = $input->subject;
        $request->message      = $input->message;
        $request->from         = $user->email;
        $request->to           = [$this->config->get('ep.email_address')];
        $request->cc           = array_unique(array_filter((array) $input->cc)) ?: null;
        $request->bcc          = array_unique(array_filter((array) $input->bcc)) ?: null;
        $request->save();

        // Add Files
        $request->files = $this->disks->getDisk($request)->storeToFiles($input->files);
        $request->save();

        // Notes
        if ($object instanceof Document) {
            $note                = new Note();
            $note->user          = $request->user;
            $note->document      = $object;
            $note->organization  = $request->organization;
            $note->changeRequest = $request;
            $note->note          = null;
            $note->pinned        = false;
            $note->save();
        }

        // Send Email
        $this->mailer->send(new RequestChange($request));

        // Return
        return $request;
    }
}
