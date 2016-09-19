<?php

/**
 * Email
 *
 * In addition to setters and getters it also provides
 * methods for converting into a Zend\Mail\Message object, and
 * for creating the email body by rendering a template.
 *
 * @author Evan
 */
namespace IvantageMail\Entity;

use Zend\Mail\Message;
use Zend\Mime\Part as MimePart;
use Zend\Mime\Message as MimeMessage;
use Zend\View\Model\ViewModel;

class Email implements EmailInterface {

    protected $id;

    protected $status;

    protected $to;

    protected $cc;

    protected $bcc;

    protected $from;

    protected $replyTo;

    protected $subject;

    protected $body;

    /**
     * An associative array of filenames to paths that should
     * be included as attachments.
     *
     * @var array
     */
    protected $attachments;

    protected $options;

    /**
     * Renderer used for rendering message content.
     */
    protected $renderer;

    /**
     * Email specific configuration.
     */
    protected $config;

    /**
     * Constructor.
     *
     * Upon creation, all emails should have their
     * status set to 'queued'.
     *
     * @param {Zend\View\Renderer} $renderer Used to render message body from templates
     */
    public function __construct($renderer = null, $config = array()) {
        $this->setStatus('queued');
        $this->renderer = $renderer;
        $this->config = $config;
    }

    /**
     *
     * This magic function specifies which fields we want to store
     * when an object of this class is serialized. In particular,
     * we DON'T want to serialize the renderer since it is a framework
     * object which is not serializable due to closures.
     *
     * When it is unserialized, it will no longer have a renderer. For now,
     * this is fine because the object is only serialized when an EmailTask
     * gets sent to the JobQueue, at which point the email should already have
     * a message body and the renderer is unnecessary.
     *
     * @return array Strings corresponding to the fields that should be serialized
     */
    public function __sleep() {
        return array('id', 'status', 'to', 'from', 'replyTo', 'subject', 'body', 'options');
    }

    /**
     * @return string $id
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return string $status
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * @return array $to
     */
    public function getTo() {
        return $this->to;
    }

    /**
     * Getter for cc
     *
     * @return mixed
     */
    public function getCc() {
        return $this->cc;
    }

    /**
     * Getter for bcc
     *
     * @return mixed
     */
    public function getBcc() {
        return $this->bcc;
    }

    /**
     * @return string $from
     */
    public function getFrom() {
        return $this->from;
    }

    /**
     * @return string $replyTo
     */
    public function getReplyTo() {
        return $this->replyTo;
    }

    /**
     * @return string $subject
     */
    public function getSubject() {
        return $this->subject;
    }

    /**
     * @return string $body
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * Getter for attachments
     *
     * @return mixed
     */
    public function getAttachments() {
        return $this->attachments;
    }


    /**
     * @return string $options
     */
    public function getOptions() {
        return $this->options;
    }

    /**
     * @return PhpRenderer $renderer
     */
    public function getRenderer() {
        return $this->renderer;
    }

    /**
     * @param string $id
     * @return IvantageMail\Entity\Email The modified object.
     */
    public function setId($id) {
        $this->id = $id;
        return ($this);
    }

    /**
     * @param string $status
     * @return IvantageMail\Entity\Email The modified object.
     */
    public function setStatus($status) {
        $this->status = $status;
        return ($this);
    }

    /**
     * @param mixed $to
     * @return IvantageMail\Entity\Email The modified object.
     */
    public function setTo($to) {
        // Convert strings to a one item array
        if(!is_array($to)) {
            $to = array($to);
        }
        $this->to = $to;
        return ($this);
    }

    /**
     * Setter for cc
     *
     * @param mixed $cc Value to set
     * @return self
     */
    public function setCc($cc) {
        if(!is_array($cc)) {
            $cc = array($cc);
        }
        $this->cc = $cc;
        return ($this);
    }

    /**
     * Setter for bcc
     *
     * @param mixed $bcc Value to set
     * @return self
     */
    public function setBcc($bcc) {
        if(!is_array($bcc)) {
            $bcc = array($bcc);
        }
        $this->bcc = $bcc;
        return ($this);
    }

    /**
     * @param string $from
     * @return IvantageMail\Entity\Email The modified object.
     */
    public function setFrom($from) {
        $this->from = $from;
        return ($this);
    }

    /**
     * @param string $replyTo
     * @return IvantageMail\Entity\Email The modified object.
     */
    public function setReplyTo($replyTo) {
        $this->replyTo = $replyTo;
        return ($this);
    }

    /**
     * @param string $subject
     * @return IvantageMail\Entity\Email The modified object.
     */
    public function setSubject($subject) {
        $this->subject = $subject;
        return ($this);
    }

    /**
     * @param string $body
     * @return IvantageMail\Entity\Email The modified object.
     */
    public function setBody($body) {
        $this->body = $body;
        return ($this);
    }

    /**
     * Setter for attachments
     *
     * @param mixed $attachments Value to set
     * @return self
     */
    public function setAttachments($attachments) {
        $this->attachments = $attachments;
        return $this;
    }

    /**
     * @param string $options
     * @return IvantageMail\Entity\Email The modified object.
     */
    public function setOptions($options) {
        $this->options = $options;
        return ($this);
    }

    /**
     * @param PhpRenderer $renderer
     */
    public function setRenderer($renderer) {
        $this->renderer = $renderer;
        return ($this);
    }

    /**
     * Getter for config
     *
     * @return mixed
     */
    public function getConfig() {
        return $this->config;
    }

    /**
     * Setter for config
     *
     * @param mixed $config Value to set
     * @return self
     */
    public function setConfig($config) {
        $this->config = $config;
        return $this;
    }


    /**
     *
     * Creates a Zend\Mail\Message object out of the info
     * contained in this IvantageMail\Entity\Email object.
     *
     * @return Zend\Mail\Message $message
     */
    public function toMessage() {
        $message = new Message();

        // The body of the message will consist of the message
        // content itself attachments if there are any.
        $bodyParts = new MimeMessage();

        // Add the body text
        if(!empty($this->body)) {
            $bodyMessage = new MimePart($this->body);
            $bodyMessage->type = 'text/html';
            $bodyParts->addPart($bodyMessage);
        }

        $message->addFrom($this->from)
                ->addTo($this->to)
                ->addReplyTo($this->replyTo)
                ->setSubject($this->subject);

        if(!empty($this->attachments)) {
            foreach ($this->attachments as $name => $path) {
                // Get the mime-type of the file
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimetype = finfo_file($finfo, $path);
                finfo_close($finfo);

                $at = new MimePart(file_get_contents($path));
                $at->type = $mimetype;
                $at->disposition = \Zend\Mime\Mime::DISPOSITION_ATTACHMENT;
                $at->encoding = \Zend\Mime\Mime::ENCODING_BASE64;
                $at->filename = $name;

                $bodyParts->addPart($at);
            }
        }

        // Set the body of the message to the MimeMessage which holds
        // the text content and (optional) attachments
        if(!empty($this->body) || !empty($this->attachments)) {
            $message->setBody($bodyParts);
        }

        return $message;
    }

    /**
     *
     * Renders a template using the provided data
     *
     * @param    string $tpl
     * @param    array  $data
     * @return  string Rendered content
     */
    public function renderContent($tpl, array $data) {
        // Create a view model using the template and data provided
        $viewModel = new ViewModel($data);
        $viewModel->setTemplate($tpl);

        // Return the rendered content
        return $this->renderer->render($viewModel);
    }

    /**
     *
     * Creates the body of the message by rendering the template
     * with the given data.
     *
     * @param string $tpl
     * @param array $data
     */
    public function renderBody($tpl, array $data) {
        return ($this->setBody($this->renderContent($tpl, $data)));
    }


    /**
     * Render the specified template into the iVantage layout and
     * set the body of the email to the rendered content.
     *
     * @param  {string} $tpl  The name of the template register in the View Manager
     * @param  {array}  $data Data used to render the template
     * @return Email    $this
     */
    public function renderIvantageBody($tpl, array $data) {
        // Render the main message content
        $content = $this->renderContent($tpl, $data);
        $options = array(
            'content' => $content,
            'website' => $this->config['template']['website'],
            'telephone' => $this->config['template']['telephone'],
            'email' => $this->config['template']['email'],
            'logo' => $this->config['template']['logo'],
            'title' => 'New Message From ' . $this->config['template']['company'],
            'company' => $this->config['template']['company'],
            'signature' => 'Best Regards,<br>&mdash; ' . $this->config['template']['contact_name']
        );

        // Render the body of the message inside the general iVantage layout.
        return ($this->setBody($this->renderContent('layout', $options)));
    }

}
