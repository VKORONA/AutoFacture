<?php

namespace Crater\Mail;

use Crater\Models\EmailLog;
use Crater\Models\Estimate;
use Crater\Models\EstimateAttachment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Vinkla\Hashids\Facades\Hashids;

class SendEstimateMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public $data = [];

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function build()
    {
        $log = EmailLog::create([
            'from' => $this->data['from'],
            'to' => $this->data['to'],
            'subject' => $this->data['subject'],
            'body' => $this->data['body'],
            'mailable_type' => Estimate::class,
            'mailable_id' => $this->data['estimate']['id'],
        ]);

        $log->token = Hashids::connection(EmailLog::class)->encode($log->id);
        $log->save();

        $this->data['url'] = route('estimate', ['email_log' => $log->token]);

        $mailContent = $this->from($this->data['from'], config('mail.from.name'))
            ->subject($this->data['subject'])
            ->markdown('emails.send.estimate', ['data', $this->data]);

        if ($this->data['attach']['data']) {
            $mailContent->attachData(
                $this->data['attach']['data']->output(),
                $this->data['estimate']['estimate_number'].'.pdf'
            );
        }

        EstimateAttachment::query()
            ->where('estimate_id', $this->data['estimate']['id'])
            ->where('include_in_email', true)
            ->orderBy('sort_order')
            ->get()
            ->each(function (EstimateAttachment $attachment) use ($mailContent): void {
                $mailContent->attachFromStorageDisk(
                    $attachment->disk,
                    $attachment->path,
                    $attachment->original_name,
                    ['mime' => $attachment->mime_type],
                );
            });

        return $mailContent;
    }
}
