<?php

namespace App\Mail;

use App\CoreService\CoreException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendGeneratedPassword extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(array $data)
    {
        //
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // env('MAIL_USERNAME')
        if ($this->data['type'] == "new_account") {
            return $this->from('noreply@hkatrom.com', 'HKA TROM')->subject('Akun Berhasil Dibuat')->view('emails/newAccountMail')->with(["data" => $this->data]);
        } elseif ($this->data['type'] == "reset_password") {
            return $this->from('noreply@hkatrom.com', 'HKA TROM')->subject('Akun HKA TROM')->view('emails/resetPasswordMail')->with(["data" => $this->data]);
        }
    }
}
