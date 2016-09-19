<?php

namespace IvantageMailTest\Entity;

use IvantageMailTest\Bootstrap;
use IvantageMail\Entity\Mailman;
use IvantageMail\Entity\Email;
use PHPUnit_Framework_TestCase;

class MailmanEntityTest extends PHPUnit_Framework_TestCase {

    public function testEntityCanBeCreated() {
        $serviceManager = Bootstrap::getServiceManager();
        $mailman = $serviceManager->get('IvantageMail\Entity\Mailman');

        $this->assertNotNull($mailman->getTransport(), "Mailman created by the service manager should have an associated transport.");
    }

    public function testSendEmail_RestrictDomains_WontSendMessage() {
        $mockTransport = $this->getMockBuilder('Zend\Mail\Transport\Smtp')->getMock();
        $allowedEmailDomains = array(
            'ivantagehealth.com'
        );

        $mailman = new Mailman($mockTransport, $allowedEmailDomains);

        $email = new Email();
        $email->setTo(array('foo@bar.com'));

        $mockTransport->expects($this->never())->method('send');
        $mailman->sendEmail($email);
    }

    public function testSendEmail_AllDomainAllowed_WillSendMessage() {
        $mockTransport = $this->getMockBuilder('Zend\Mail\Transport\Smtp')->getMock();
        $allowedEmailDomains = array(
            'ivantagehealth.com'
        );

        $mailman = new Mailman($mockTransport, $allowedEmailDomains);

        $stubEmail = $this->getMockBuilder('IvantageMail\Entity\Email')->getMock();
        $stubEmail->method('getTo')->will($this->returnValue(['foo@ivantagehealth.com']));
        $stubEmail->method('toMessage')->will($this->returnValue(new \Zend\Mail\Message()));

        $mockTransport->expects($this->once())->method('send');
        $mailman->sendEmail($stubEmail);
    }

    public function testSendEmail_SomeDomainsAllowed_WillFilterRecipients() {
        $stubTransport = $this->getMockBuilder('Zend\Mail\Transport\Smtp')->getMock();
        $allowedEmailDomains = array(
            'ivantagehealth.com'
        );

        $mailman = new Mailman($stubTransport, $allowedEmailDomains);

        $mockEmail = $this->getMockBuilder('IvantageMail\Entity\Email')->getMock();
        $mockEmail->method('getTo')
                ->will($this->returnValue(['foo@ivantagehealth.com', 'foo@bar.com']));
        $mockEmail->method('toMessage')->will($this->returnValue(new \Zend\Mail\Message()));

        $mockEmail->expects($this->once())->method('setTo')
                ->with(['foo@ivantagehealth.com']);
        $mailman->sendEmail($mockEmail);
    }

}
