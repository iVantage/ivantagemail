<?php

/**
 * A utility service that provides simple mailing function
 * by encapsulating the creation of emails, job queue tasks,
 * and sending.
 *
 * @package IvantageMail
 * @copyright 2014 iVantage Health Analytics, Inc.
 */
namespace IvantageMail\Service;

class PostOffice
{

    protected $emailFactory;

    protected $emailTaskFactory;

    protected $mailman;

    public function __construct($emailFactory, $emailTaskFactory, $mailman)
    {
        $this->emailFactory = $emailFactory;
        $this->emailTaskFactory = $emailTaskFactory;
        $this->mailman = $mailman;
    }

    /**
     * Send a templated email via the job queue.
     *
     * @param  {string} $from          Email sender address
     * @param  {array}  $to            Email recipients
     * @param  {string} $subject       Email subject
     * @param  {string} $body          Email body
     * @param  {string} $templateId    UUID of the SendGrid template
     * @param  {array}  $substitutions Assoc. array of template subtitutions
     * @param  {array}  $categories    Array of categories of the email
     * @return {int}                   Job queue id of the generated job
     */
    public function sendTemplateEmail($from, $to, $subject, $body, $templateId,
        $substitutions = array(), $categories = array())
    {
        $email = $this->getEmail();
        $email->setFrom($from)
            ->setReplyTo($from)
            ->setTo($to)
            ->setSubject($subject)
            ->setBody($body);

        $headers = $this->createSendgridHeader($to, $templateId, $substitutions, $categories);

        $emailTask = $this->getEmailTask($email, $this->mailman, $headers);
        $jobId = $emailTask->execute(
            'https://' . $_SERVER['HTTP_HOST'] . '/jobqueue');
        return $jobId;
    }

    /**
     * Get a new email object.
     *
     * Uses the email factory to create an Email object.
     *
     * @return {IvantageMail\Entity\Email}
     */
    public function getEmail()
    {
        return $this->emailFactory->__invoke();
    }

    /**
     * Get a new email task object.
     *
     * Uses the email task factory to create the object.
     *
     * @param  {IvantageMail\Entity\Email}    $email
     * @param  {IvantageMail\Entity\Mailman}  $mailman
     * @param  {array}                        $headers
     * @return {IvantageMail\Tasks\EmailTask}
     */
    public function getEmailTask($email, $mailman, $headers)
    {
        return $this->emailTaskFactory->__invoke($email, $mailman, $headers);
    }

    /**
     * Create a sendgrid header to specify email template
     * and other options.
     * @param  {array}  $to            An array of recipients
     * @param  {string} $templateId    UUID of the SendGrid template
     * @param  {array}  $substitutions Assoc. array of template subtitutions
     * @param  {array}  $categories    Array of categories of the email
     * @return {array}                 Key value pair for the header
     */
    public function createSendgridHeader($to,
        $templateId,
        $substitutions = array(),
        $categories = array())
    {
        if(!is_array($to)) {
            $to = array($to);
        }

        $options = [
            'to' => $to,
            'filters' => [
                'templates' => [
                    'settings' => [
                        'enabled' => 1,
                        'template_id' => $templateId
                    ]
                ]
            ]
        ];

        if(!empty($substitutions)) {
            $options['sub'] = $substitutions;
        }
        if(!empty($categories)) {
            $options['category'] = $categories;
        }

        return ['X-SMTPAPI' => json_encode($options)];
    }

}
