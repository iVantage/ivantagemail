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

use IvantageMail\Tasks\TransactionalTemplateEmailTask;
use \SendGrid\Mail\From;
use \SendGrid\Mail\To;
use \SendGrid\Mail\Mail;

class PostOffice
{

    protected $emailFactory;

    protected $emailTaskFactory;

    protected $mailman;

    protected $apiKey;

    public function __construct($emailFactory, $emailTaskFactory, $mailman, $apiKey)
    {
        $this->emailFactory = $emailFactory;
        $this->emailTaskFactory = $emailTaskFactory;
        $this->mailman = $mailman;
        $this->apiKey = $apiKey;
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
            'http://' . $_SERVER['HTTP_HOST'] . '/jobqueue');
        return $jobId;
    }

    /**
     * Sends a SendGrid template email to one or more recipients. Sending messages
     * in this way bypasses the Zend Server job queue to avoid message size restrictions.
     *
     * @param string $fromAddress Email address of the sender
     * @param array $toAddresses List of recipient email addresses.
     * @param string $subject Email subject
     * @param string $templateId The ID of the Sendgrid legacy/transactional template
     * @param array|null $toNames List of recipient names
     * @param array|null $toSubstitutions List of template substitutions for the recipients
     * @param array $categories Optional categories to apply to the message.
     * @return \SendGrid\Response
     * @throws \SendGrid\Mail\TypeException
     */
    public function sendBulkTemplateEmail($fromAddress, array $toAddresses, $subject,
                                          $templateId, array $toNames = null,
                                          array $toSubstitutions = null, array $categories = [])
    {
        if(!is_null($toNames) && count($toAddresses) !== count($toNames)) {
            throw new \InvalidArgumentException("Recipient email address and name arrays must be of equal length");
        }
        if(!is_null($toSubstitutions) && count($toAddresses) !== count($toSubstitutions)) {
            throw new \InvalidArgumentException("Recipient email address and substitution arrays must be of equal length");
        }

        if(is_null($toNames)) {
            $toNames = [];
        }
        if(is_null($toSubstitutions)) {
            $toSubstitutions = [];
        }

        $from = new From($fromAddress);
        $tos = array_map(function ($to, $name, $subs) {
            $to = new To($to);
            if(!empty($name)) {
                $to->setName($name);
            }
            if(!empty($subs)) {
                $to->setSubstitutions($subs);
            }
            return $to;
        }, $toAddresses, $toNames, $toSubstitutions);

        $email = new Mail($from, $tos, $subject);
        $email->setTemplateId($templateId);
        if(!empty($categories)) {
            $email->addCategories($categories);
        }

        $sendgrid = new \SendGrid($this->apiKey);
        return $sendgrid->send($email);
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
