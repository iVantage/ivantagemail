<?php

/**
 * Mailman --
 * This class encapsulates transport and sending of emails.
 * It is registered with the service manager in module.config.php.
 *
 * @author Evan
 *
 */
namespace IvantageMail\Entity;

use Laminas\Mail\Transport;
use Laminas\Mail\Transport\Smtp as SmtpTransport;
use Laminas\Mail\Message;

use IvantageMail\Entity\Email;
use IvantageMail\Service\Utils;

class Mailman {

    protected $transport;
    protected $allowedEmailDomains;

    /**
     * Constructor
     * @param Laminas\Mail\Transport\Smtp $transport
     */
    function __construct(SmtpTransport $transport, $allowedEmailDomains = array()) {
        $this->setTransport($transport);
        $this->allowedEmailDomains = $allowedEmailDomains;
    }

    /**
     * @return Laminas\Mail\Transport The transport used to send messages.
     */
    public function getTransport() {
        return $this->transport;
    }

    /**
     * @param SmtpTransport $transport
     */
    public function setTransport(SmtpTransport $transport) {
        $this->transport = $transport;
    }

    /**
     * Uses the Mailman's transport to send a message.
     *
     * @param IvantageMail\Entity\EmailInterface $email
     * @param {array} $headers Key-value pairs of headers to add to the message
     */
    public function sendEmail(EmailInterface $email, $headers = array()) {
        // If there is a restricted list of email domains to send to, ensure
        // that each recipient matches one of them. If it doesn't, don't send
        // the email!
        if(!empty($this->allowedEmailDomains)) {
            $toEmails = $email->getTo();
            $toEmails = array_filter($toEmails, function($email)  {
                return Utils::emailMatchesDomain($email, $this->allowedEmailDomains);
            });
            if(empty($toEmails)) {
                // All the emails were filtered out so there's no point continuing
                return;
            }
            $email->setTo($toEmails);
        }

        // Convert the email to a Laminas\Mail\Message, which the transport
        // is capable of sending.
        $message = $email->toMessage();

        foreach ($headers as $key => $value) {
            $message->getHeaders()->addHeaderLine($key, $value);
        }

        $this->getTransport()->send($message);
    }

}
