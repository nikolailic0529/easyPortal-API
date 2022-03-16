<?php declare(strict_types = 1);

namespace App\Mail;

use App\Models\Asset;
use App\Models\ChangeRequest;
use App\Models\Customer;
use App\Models\Document;
use App\Models\File;
use App\Models\Organization;
use App\Rules\ContractId;
use App\Rules\QuoteId;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use function app;
use function get_class;

class RequestChange extends Mailable {
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected ChangeRequest $request,
        protected Asset|Document|Customer|Organization $model,
    ) {
        // empty
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build() {
        $mail = $this->subject($this->request->subject);
        if ($this->request->cc) {
            $mail = $mail->cc($this->request->cc);
        }

        if ($this->request->bcc) {
            $mail = $mail->bcc($this->request->bcc);
        }

        foreach ($this->request->files as $file) {
            /** @var File $file */
            $mail = $mail->attachFromStorageDisk($file->disk, $file->path, $file->name);
        }

        $type  = '';
        $title = '';
        switch (get_class($this->model)) {
            case Asset::class:
                $type  = 'asset';
                $title = $this->model->product->name;
                break;
            case Customer::class:
                $type  = 'customer';
                $title = $this->model->name;
                break;
            case Document::class:
                // checking document type if Contact or Quote.
                $title = $this->model->number;
                if (app()->make(ContractId::class)->passes(null, $this->model->getKey())) {
                    $type = 'contract';
                } elseif (app()->make(QuoteId::class)->passes(null, $this->model->getKey())) {
                    $type = 'quote';
                } else {
                    // empty
                }
                break;
            case Organization::class:
                $type  = 'organization';
                $title = $this->model->name;
                break;
            default:
                // empty
                break;
        }

        return $mail->to($this->request->to)->markdown('change_request', [
            'request' => $this->request,
            'type'    => $type,
            'title'   => $title,
        ]);
    }
}
