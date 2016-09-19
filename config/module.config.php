<?php

use Zend\View\Renderer\PhpRenderer;
use IvantageMail\Entity\Email;
use Zend\Db\Adapter\Adapter;
use Zend\Http\Client;

return array(
    'doctrine' => array(
        'driver' => array(
            'ivantagemail_entities' => array(
                'class' =>'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => array(__DIR__ . '/../src/IvantageMail/Entity')
            ),

            'orm_default' => array(
                'drivers' => array(
                    'IvantageMail\Entity' => 'ivantagemail_entities'
                )
            )
        )
    ),
    'controllers' => array(
        'invokables' => array(
            'IvantageMail\Controller\EmailRest' => 'IvantageMail\Controller\EmailRestController'
        ),
    ),

    'router' => array(
        'routes' => array(
            'email-rest' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/email-rest[/][/:id]',
                    'constraints' => array(
                        'id' => '\w{8}-\w{4}-\w{4}-\w{4}-\w{12}',
                    ),
                    'defaults' => array(
                        'controller' => 'IvantageMail\Controller\EmailRest',
                    ),
                ),
            ),
            'email-send' => array(
                'type' => 'literal',
                'options' => array(
                    'route' => '/email/send',
                    'defaults' => array(
                        'controller' => 'IvantageMail\Controller\EmailRest',
                        'action'     => 'send',
                    ),
                ),
            ),
            'email-get-queued' => array(
                'type' => 'literal',
                'options' => array(
                    'route' => '/email/queued',
                    'defaults' => array(
                        'controller' => 'IvantageMail\Controller\EmailRest',
                        'action'     => 'getQueued',
                    ),
                ),
            ),
            'email-start-queue' => array(
                'type' => 'literal',
                'options' => array(
                    'route' => '/email/startQueue',
                    'defaults' => array(
                        'controller' => 'IvantageMail\Controller\EmailRest',
                        'action'     => 'startQueue',
                    ),
                ),
            ),
        ),
    ),

    'view_manager' => array(
        /*
         * Views are assigned based on the normalized path to the controller that
         * triggered the action.
         * Key => value pairs designated here provide the associations
         *
         * Since the ViewRenderer is passed to email instances, any templates specified
         * here will be available to emails for rendering the message body.
         */
        'template_map' => array(
            'ivantage-mail/email-rest/send'    => __DIR__ . '/../view/IvantageMail/sendEmail.phtml',
            'ivantage-mail/email-rest/start-queue' => __DIR__ . '/../view/IvantageMail/sendEmail.phtml',
            'layout' => __DIR__ . '/../view/IvantageMail/templates/layout.phtml'
        ),
        'strategies' => array(
            'ViewJsonStrategy'
        )
    ),

    'service_manager' => array(
        'factories' => array(
            /* --------------------------------------------------
             * Email
             * -------------------------------------------------- */
            'IvantageMail\Entity\Email' => function($sm) {
                $renderer = $sm->get('ViewRenderer');
                $config = $sm->get('config')['ivantagemail'];
                $email = new \IvantageMail\Entity\Email($renderer, $config);
                return( $email );
            },
            /* --------------------------------------------------
             * Mailman
             * -------------------------------------------------- */
            'IvantageMail\Entity\Mailman' => function($sm) {
                $transport = $sm->get('Zend\Mail\Transport\Smtp');
                $allowedEmailDomains = array();
                $config = $sm->get('config')['ivantagemail'];
                if(isset($config['allowed_email_domains'])) {
                    $allowedEmailDomains = $config['allowed_email_domains'];
                }
                $mailman = new \IvantageMail\Entity\Mailman($transport, $allowedEmailDomains);
                return $mailman;
            },
            /* --------------------------------------------------
             * Factory for creating new email tasks
             * -------------------------------------------------- */
            'EmailTaskFactory' => function($sm) {
                $factory = function($email, $mailman, $headers = array()) {
                    $emailTask = new \IvantageMail\Tasks\EmailTask($email, $mailman, $headers);
                    return $emailTask;
                };
                return $factory;
            },
            /* --------------------------------------------------
             * Factory for creating new emails
             * -------------------------------------------------- */
            'EmailFactory' => function($sm) {
                $renderer = $sm->get('ViewRenderer');
                $config = $sm->get('config')['ivantagemail'];
                $factory = function() use ($renderer, $config) {
                    $email = new \IvantageMail\Entity\Email($renderer, $config);
                    return $email;
                };
                return $factory;
            },
            /* --------------------------------------------------
             * The virtual Pony Express
             * -------------------------------------------------- */
            'IvantageMail\Service\PostOffice' => function($sm) {
                $emailFactory = $sm->get('EmailFactory');
                $emailTaskFactory = $sm->get('EmailTaskFactory');
                $mailman = $sm->get('IvantageMail\Entity\Mailman');

                return new \IvantageMail\Service\PostOffice(
                    $emailFactory,
                    $emailTaskFactory,
                    $mailman
                );
            },
            'IvantageMail\Service\SendGrid' => function($sm) {
                $client = new Client();
                $curlAdapter = new \Zend\Http\Client\Adapter\Curl();
                $env = getenv('APPLICATION_ENV');
                if($env !== 'production') {
                    $curlAdapter->setOptions(array(
                        'curloptions' => array(
                            CURLOPT_SSL_VERIFYPEER => 0,
                            CURLOPT_SSL_VERIFYHOST => 0,
                        )
                    ));
                }
                $client->setAdapter($curlAdapter);

                $apiKey = $sm->get('config')['ivantagemail']['sendgrid']['api_key'];

                return new \IvantageMail\Service\SendGrid($client, $apiKey);
            }
        )
    )

);
