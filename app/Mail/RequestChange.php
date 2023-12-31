<?php declare(strict_types = 1);

namespace App\Mail;

use App\Mail\Concerns\DefaultRecipients;
use App\Models\Asset;
use App\Models\ChangeRequest;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequestChange extends Mailable {
    use Queueable;
    use SerializesModels;
    use DefaultRecipients;

    public function __construct(
        protected ChangeRequest $request,
    ) {
        // empty
    }

    public function build(Repository $config): void {
        $object = $this->request->object;
        $title  = $object->getKey();
        $type   = $object->getMorphClass();

        if ($object instanceof Asset) {
            $title = $object->product->name ?? "#{$object->getKey()}";
        } elseif ($object instanceof Customer) {
            $title = $object->name;
        } elseif ($object instanceof Organization) {
            $title = $object->name;
        } elseif ($object instanceof Document) {
            $title = $object->number ?? "#{$object->getKey()}";

            if ($object->is_contract) {
                $type = 'Contract';
            } elseif ($object->is_quote) {
                $type = 'Quote';
            } else {
                // empty
            }
        } else {
            // empty
        }

        $to  = $this->request->to;
        $cc  = (array) $this->request->cc;
        $bcc = $this->getDefaultRecipients(
            $config,
            $this->request,
            $this->request->bcc,
            $this->request->user->email,
        );

        $this
            ->subject($this->request->subject)
            ->to($to)
            ->cc($cc)
            ->bcc($bcc)
            ->markdown('change_request', [
                'request' => $this->request,
                'type'    => $type,
                'title'   => $title,
            ]);

        foreach ($this->request->files as $file) {
            $this->attach($file);
        }
    }
}
