<?php

/**
 *
 * This task is started as a scheduled task. It checks the email table
 * to get a list of queued messages. It takes the first one from the list,
 * sends it, and marks it as sent.
 *
 * @author Evan
 *
 */
namespace IvantageMail\Tasks;

use IvantageJobQueue\Tasks\AbstractJobQueueTask;
use IvantageMail\Entity\Email;
use IvantageMail\Entity\Mailman;

use Zend\Http\Client;
use Zend\Http\Request;
use Zend\Http\Response;

class PollEmailQueueTask extends AbstractJobQueueTask {

    private $_mailman;

    public function __construct(Mailman $mailman) {
        $this->_mailman = $mailman;
    }

    public function _execute() {
        // Make an HTTP request to GET the queued messages
        $request = new Request();
        $request->setMethod('GET');
        // [TODO] this should be generic
        $request->setUri('http://' . $_SERVER['HTTP_HOST'] . '/email/queued');
        $request->getHeaders()->addHeaders(array(
            'Content-Type' => 'application/json'
        ));

        $client = new Client();
        $response = $client->dispatch($request);
        $json = json_decode($response->getBody(), true);
        $data = $json['data'];

        if(count($data) > 0) {
            // Get the first message
            $message = $data[0];
            // Create an email object using all of its information
            $email = new Email();
            $email->setId($message['id'])
                ->setFrom($message['from'])
                ->setTo($message['to'])
                ->setSubject($message['subject'])
                ->setBody($message['body'])
                ->setStatus($message['status'])
                ->setReplyTo($message['replyTo']);

            // Send it!
            $this->_mailman->sendEmail($email);

            // Make a request to update that email and change its status to 'sent'
            $request = new Request();
            $request->setMethod('PUT');
            // [TODO] this should be generic
            $request->setUri('http://' . $_SERVER['HTTP_HOST'] . '/email-rest/' . $message['id']);
            $request->setContent('{"status":"sent"}');
            $response = $client->dispatch($request);
        }
    }
}