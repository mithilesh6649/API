<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendEmailVerificationLink extends Mailable
{
    use Queueable, SerializesModels;

    protected $details;

    protected $randomNumber;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($details, $randomNumber)
    {
        $this->details = $details;
        $this->randomNumber = $randomNumber;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: 'Send Email Verification Link',
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
       return (new Content)
        ->view('emails.SendEmailVerificationLink')
        ->with(['mail_data' => $this->details, 'otp' => $this->randomNumber]);
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
