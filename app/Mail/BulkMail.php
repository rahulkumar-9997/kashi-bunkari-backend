<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BulkMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function build()
    {
        return $this
            ->subject('Kasi Bunkari Website Enquiry Bulk Enquiry Form : ' . ($this->data['subject'] ?? 'No Subject'))
            ->replyTo(
                $this->data['email'],
                $this->data['name']
            )
            ->view('emails.bulkMail')
            ->with([
                'data' => $this->data,
            ]);
    }
}
