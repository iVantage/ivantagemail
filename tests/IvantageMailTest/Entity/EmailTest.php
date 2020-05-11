<?php

namespace IvantageMailTest\Entity;

use IvantageMailTest\Bootstrap;
use IvantageMail\Entity\Email;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\Resolver\TemplatePathStack;
use PHPUnit\Framework\TestCase;

class EmailEntityTest extends TestCase {

    protected $serviceManager;
    protected $renderer;
    protected $testEmail;

    protected function setUp() {
        $this->serviceManager = Bootstrap::getServiceManager();

        $renderer = new PhpRenderer();
        $resolver = new TemplatePathStack();
        $resolver->addPath(__DIR__ . '/../../../view/IvantageMail/templates');
        $renderer->setResolver($resolver);
        $this->renderer = $renderer;

        $config = array(
            'template' => array(
                'website' => 'www.mywebsite.com',
                'email' => 'email@mywebsite.com',
                'telephone' => '555-555-5555',
                'logo' => 'urlformylogo.png',
                'company' => 'My company name',
                'contact_name' => 'The Team'
            )
        );

        $email = new Email($this->renderer, $config);
        $email->setId('1')
            ->setFrom('gary@pokemon.com')
            ->setTo('ash@pokemon.com')
            ->setReplyTo('gary@pokemon.com')
            ->setSubject('Hey, Ash!')
            ->setBody('Smell ya later!');
            // ->setRenderer($this->renderer);
        $this->testEmail = $email;
    }

    public function testEntityCanBeCreated() {
        $email = new Email($this->renderer);

        $this->assertNull($email->getId(), "Email 'id' should initially be null");
        $this->assertNull($email->getTo(), "Email 'to' should initially be null");
        $this->assertNull($email->getReplyTo(), "Email 'replyTo' should initially be null");
        $this->assertNull($email->getFrom(), "Email 'from' should initially be null");
        $this->assertNull($email->getSubject(), "Email 'subject' should initially be null");
        $this->assertNull($email->getBody(), "Email 'body' should initially be null");
        $this->assertEquals('queued', $email->getStatus(), "Email 'status' should initially be 'queued'");
        $this->assertNull($email->getOptions(), "Email 'options' should initially be null");
        $this->assertNotNull($email->getRenderer(), "Email 'renderer' should have been initialized.");
    }

    public function testToMessage() {
        $message = $this->testEmail->toMessage();

        $this->assertInstanceOf('Zend\Mail\Message', $message);
    }

    public function testRenderBody() {
        $email = $this->testEmail;
        $email->renderBody('test', array('name' => 'Evan'));

        $expected = 'Hello there, Evan. Testing.';
        $actual   = $email->getBody();

        $this->assertEquals($expected, $actual);
    }

    public function testSetTo() {
        $email = new Email();

        $email->setTo('gary.oak@pokemon.com');
        $this->assertEquals(array('gary.oak@pokemon.com'), $email->getTo(),
            'setTo() function should convert string input to a single item array.');

        $toList = array(
            'gary.oak@pokemon.com',
            'ash.ketchum@pokemon.com'
        );
        $email->setTo($toList);
        $this->assertEquals($toList, $email->getTo(),
            'setTo() function should not make any modifications when passed in an array of strings.');
    }

    public function testSetCc() {
        $email = new Email();

        $email->setCc('gary.oak@pokemon.com');
        $this->assertEquals(array('gary.oak@pokemon.com'), $email->getCc(),
            'setCc() function should convert string input to a single item array.');

        $ccList = array(
            'gary.oak@pokemon.com',
            'ash.ketchum@pokemon.com'
        );
        $email->setCc($ccList);
        $this->assertEquals($ccList, $email->getCc(),
            'setCc() function should not make any modifications when passed in an array of strings.');
    }

    public function testSetBcc() {
        $email = new Email();

        $email->setBcc('gary.oak@pokemon.com');
        $this->assertEquals(array('gary.oak@pokemon.com'), $email->getBcc(),
            'setBcc() function should convert string input to a single item array.');

        $bccList = array(
            'gary.oak@pokemon.com',
            'ash.ketchum@pokemon.com'
        );
        $email->setBcc($bccList);
        $this->assertEquals($bccList, $email->getBcc(),
            'setBcc() function should not make any modifications when passed in an array of strings.');
    }

    public function testAttachments() {
        $email = $this->testEmail;

        $attachments = array(
            'pika.png' => __DIR__ . '/../../fixtures/Pikachu_Sprite.png'
        );
        $email->setAttachments($attachments);

        $this->assertEquals($attachments, $email->getAttachments(),
            'Retrieved attachments should be the same ones that were set.');

        $message = $email->toMessage();
        $this->assertCount(2, $message->getBody()->getParts(), 'There should be two parts in the MimeMessage body.');
    }

    public function testSerialization() {
        // The renderer object that is a part of the email entity is a Framework object that
        // cannot be serialized due to closures. It must be removed before serialization, so upon
        // deserialization the $renderer should be null.
        $serializedEmail = serialize($this->testEmail);
        $unserialiezedEmail = unserialize($serializedEmail);

        $this->assertNull($unserialiezedEmail->getRenderer(), 'Renderer should be null after serialization/deserialization');
    }

    public function testRenderIvantageBody() {
        $email = $this->testEmail;
        $email->renderIvantageBody('test', array('name' => 'Evan'));

        $body = $email->getBody();
        $expected = file_get_contents(__DIR__ . '/../../fixtures/rendered-ivantage-body.html');
        $expected = str_replace('{{year}}', date('Y'), $expected);

        $this->assertEquals($expected, $body, 'Rendered email body should match expected.');
    }

}
