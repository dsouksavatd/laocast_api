<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class EmailVerificationCode extends Mailable
{

    public function __construct($params)
    {
        $this->params = $params;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mail.emailVerificationCode', [
            'params' => $this->params
        ]);
    }
}
