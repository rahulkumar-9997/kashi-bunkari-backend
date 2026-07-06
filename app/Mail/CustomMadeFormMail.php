<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomMadeFormMail extends Mailable
{
    use Queueable, SerializesModels;
    public $data;
    public function __construct($data)
    {
        $this->data = $data;
    }

    public function build()
    {
        $mail = $this->subject('Nov Sac Website Enquiry : Custom made form enquiry ' . ($this->data['requestFor'] ?? 'No Subject'))
            ->view('emails.custom-made-form-mail')
            ->with(['data' => $this->data]);
        if (!empty($this->data['attachment'])) {
            $filePath = storage_path('app/public/attachment/custom-bags/' . $this->data['attachment']);
            if (file_exists($filePath)) {
                $mail->attach($filePath);
            }
        }
        return $mail;
    }
}