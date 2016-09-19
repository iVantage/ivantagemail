<?php

/**
 *
 * This task is meant to be run once for each message. It must
 * be passed in an Email and a Mailman and the Mailman will send
 * the email.
 *
 * @author Evan
 *
 */
namespace IvantageMail\Tasks;

use IvantageJobQueue\Tasks\AbstractJobQueueTask;
use IvantageMail\Entity\Email;
use IvantageMail\Entity\Mailman;

class EmailTask extends AbstractJobQueueTask {

    private $_email;
    private $_mailman;
    private $_headers;

    public function __construct(Email $email, Mailman $mailman, $headers = array()) {
        $this->_email = $email;
        $this->_mailman = $mailman;
        $this->_headers = $headers;
    }

    /**
     * Use the mailman to send the email.
     *
     * This method will be called by the job queue task. Other parts of the
     * application should not interact with it explicitly.
     */
    public function _execute() {
        // Use the mailman to deliver the message
        $this->_mailman->sendEmail($this->_email, $this->_headers);
    }

    /**
     * Execute the task on the job queue.
     *
     * @param  string $url          The URL endpoint for job queue tasks.
     * @param  array  $queueOptions Options for the job queue.
     * @return int                     The integer ID for the generated job queue task.
     */
    public function execute($url, $queueOptions = array()) {
        return parent::execute($url, $queueOptions);
    }

}
