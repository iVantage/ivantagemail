<?php

namespace IvantageMail\Tasks;

use IvantageJobQueue\Tasks\AbstractJobQueueTask;
use SendGrid\Mail\Mail;

class TransactionalTemplateEmailTask extends AbstractJobQueueTask
{
    /** @var Mail */
    private $_email;
    private $_apiKey;

    /**
     * TransactionalTemplateEmailTask constructor.
     * @param $_email
     * @param $_apiKey
     */
    public function __construct($_email, $_apiKey)
    {
        $this->_email = $_email;
        $this->_apiKey = $_apiKey;
    }

    protected function _execute()
    {
        $sendgrid = new \SendGrid($this->_apiKey);
        $sendgrid->send($this->_email);
    }
}
