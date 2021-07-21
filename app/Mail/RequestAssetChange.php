<?php declare(strict_types = 1);

namespace App\Mail;

use App\Models\ChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequestAssetChange extends Mailable {
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(
        protected ChangeRequest $request,
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
        return $mail->to($this->request->to)->markdown('asset_change_request', [
            'request' => $this->request,
        ]);
    }
}
