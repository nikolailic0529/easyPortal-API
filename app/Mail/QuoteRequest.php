<?php declare(strict_types = 1);

namespace App\Mail;

use App\Mail\Concerns\DefaultRecipients;
use App\Models\File;
use App\Models\QuoteRequest as ModelsQuoteRequest;
use App\Services\Auth\Auth;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QuoteRequest extends Mailable {
    use Queueable;
    use SerializesModels;
    use DefaultRecipients;

    public function __construct(
        protected ModelsQuoteRequest $request,
    ) {
        // empty
    }

    public function build(Repository $config, Auth $auth): void {
        $to  = $config->get('ep.email_address');
        $bcc = $this->getDefaultRecipients($config, $auth, $this->request);

        $this
            ->subject('Quote Request')
            ->to($to)
            ->bcc($bcc)
            ->markdown('quote_request', [
                'request' => $this->request,
            ]);

        foreach ($this->request->files as $file) {
            /** @var File $file */
            $this->attachFromStorageDisk($file->disk, $file->path, $file->name);
        }
    }
}
