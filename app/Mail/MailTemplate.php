<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class MailTemplate extends Mailable
{

    public function __construct($subject, $params)
    {
        $this->subject = $subject;
        $this->params = $params;    
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mail.mailTemplate', [
            'params' => $this->params
        ])->subject($this->subject);
    }
}
