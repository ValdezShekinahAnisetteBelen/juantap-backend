<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $status;
    public $template;

    public function __construct($user, $status, $template)
    {
        $this->user = $user;
        $this->status = $status;
        $this->template = $template;
    }

    public function build()
    {
        return $this->subject("Your Payment Status Update")
            ->view('emails.payment-status');
    }
}
