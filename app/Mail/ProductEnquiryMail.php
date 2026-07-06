<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProductEnquiryMail extends Mailable
{
    use SerializesModels;

    public $data;
    public $product;

    public function __construct($data, $product)
    {
        $this->data = $data;
        $this->product = $product;
    }

    public function build()
    {
        $mail = $this->subject('New Product Enquiry - Novasac')
            ->view('emails.product-enquiry')
            ->with(['data' => $this->data]);
            if (!empty($this->data['email'])) {
                $mail->replyTo($this->data['email'], $this->data['name'] ?? null);
            }             
        if (!empty($this->data['productData']['image_path'])) {
            $imagePath = storage_path(
                'app/public/images/product/large/' . $this->data['productData']['image_path']
            );
            if (file_exists($imagePath)) {
                $mail->attach($imagePath);
            }
        }
        return $mail;
    }
}
