<?php

namespace IvantageMailTest\Service;

use IvantageMailTest\Bootstrap;
use IvantageMail\Service\PostOffice;
use PHPUnit\Framework\TestCase;

class PostOfficeTest extends TestCase {


    protected function setUp()
    {
        Bootstrap::getServiceManager();
    }

    public function createPostOffice(){

        $email = new \IvantageMail\Entity\Email();
        $mailman = new \IvantageMail\Entity\Mailman(
                new \Zend\Mail\Transport\Smtp());
        $headers = [];
        $emailFactory = function() use ($email) {
            return $email;
        };
        $taskFactory = function($email, $mailman, $headers){
                return new \IvantageMail\Tasks\EmailTask($email, $mailman, $headers);
        };
        return new PostOffice($emailFactory, $taskFactory, $mailman);
    }

    public function testCreateSendgridHeader()
    {
        $postOffice = $this->createPostOffice();
        $to = [
            'dingo@atemybaby.com',
            'lemmetake@pikachu.com'
        ];
        $templateId = 'foobar';
        $substitutions = [
            '-name-' => ['Dingo', 'Pikachu'],
            '-fave-food-' => ['babies', 'berries']
        ];
        $category =['wah'];
        $headers = $postOffice->createSendgridHeader($to,
                $templateId,
                $substitutions,
                $category);
        $this->assertArrayHasKey('X-SMTPAPI', $headers,
                'It should have an X-SMTPAPI header');
        $smtpApi = $headers['X-SMTPAPI'];
        $this->assertTrue(is_string($smtpApi),
                'The header value should be a string');
        $apiOptions = json_decode($smtpApi, true);
        $this->assertEquals($to, $apiOptions['to'],
                'It should have a "to" value');
        $this->assertEquals($substitutions, $apiOptions['sub'],
                'It should have a "sub" value');
        $this->assertEquals($category, $apiOptions['category'],
                'It should have a "category" value');
        $expectedFilter = [
            'templates' => [
                'settings' => [
                    'enabled' => 1,
                    'template_id' => $templateId
                ]
            ]
        ];
        $this->assertEquals($expectedFilter, $apiOptions['filters'],
                'It should have a "filter" value');
    }

    public function testGetEmail()
    {
        $postOffice = $this->createPostOffice();
        $email = $postOffice->getEmail();
        $this->assertInstanceOf('Ivantagemail\Entity\Email', $email,
                'It should return an instance of the Email class');
    }

    public function testGetEmailTask()
    {
        $postOffice = $this->createPostOffice();
        $email = new \IvantageMail\Entity\Email();
        $mailman = new \IvantageMail\Entity\Mailman(
                new \Zend\Mail\Transport\Smtp());
        $headers = [];
        $emailTask = $postOffice->getEmailTask($email, $mailman, $headers);
        $this->assertInstanceOf('IvantageMail\Tasks\EmailTask', $emailTask,
                'It should return an instance of the EmailTask class');
    }

    public function testSendTemplateEmail()
    {
        $mockEmail = $this->getMockBuilder('IvantageMail\Entity\Email')
                ->getMock();
        $mockEmail->expects($this->once())
                ->method('setFrom')
                ->willReturn($mockEmail);
        $mockEmail->expects($this->once())
                ->method('setReplyTo')
                ->willReturn($mockEmail);
        $mockEmail->expects($this->once())
                ->method('setTo')
                ->willReturn($mockEmail);
        $mockEmail->expects($this->once())
                ->method('setSubject')
                ->willReturn($mockEmail);
        $mockEmail->expects($this->once())
                ->method('setBody')
                ->willReturn($mockEmail);
        $emailFactory = function() use ($mockEmail) {
            return $mockEmail;
        };
        $mockTask = $this->getMockBuilder('IvantageMail\Tasks\EmailTask')
                ->disableOriginalConstructor()
                ->getMock();
        $mockTask->expects($this->once())
                ->method('execute')
                ->willReturn(1);
        $taskFactory = function($email, $mailman, $headers)
                use ($mockTask) {
            return $mockTask;
        };
        $mailman = $this->getMockBuilder('IvantageMail\Entity\Mailman')
                ->disableOriginalConstructor()
                ->getMock();
        $postOffice = new PostOffice($emailFactory, $taskFactory, $mailman);
        $from = 'foo@bar.com';
        $to = 'dingo@atemybaby.com';
        $subject = '50% off nail trimmers';
        $body = 'buy now and get a free baby!';
        $templateId = 'dingo';
        $jobId = $postOffice->sendTemplateEmail($from, $to, $subject, $body,
                $templateId);
        $this->assertEquals(1, $jobId,
                'It should return an integer jobqueue id');
    }

}
