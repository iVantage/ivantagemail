<?php

use Laminas\View\Resolver\TemplatePathStack;
use Laminas\View\Renderer\PhpRenderer;

return array(
    'ivantagemail' => array(
        'template' => array(
            'website' => 'www.mywebsite.com',
            'email' => 'email@mywebsite.com',
            'telephone' => '555-555-5555',
            'logo' => 'urlformylogo.png',
            'company' => 'My company name',
            'contact_name' => 'The Team'
        )
    ),
    'doctrine' => array(
        'connection' => array(
            'orm_default' => array(
                'driverClass' =>'Doctrine\DBAL\Driver\PDOSqlite\Driver',
                'params' => array(
                    'path' => ':memory:'
                )
            )
        )
    ),
    'service_manager' => array(
        'factories' => array(
            'Laminas\Mail\Transport\Smtp' => function ($sm) {
                $smtpTransport = new Laminas\Mail\Transport\Smtp();
                return $smtpTransport;
            },
            'ViewRenderer' => function($sm) {
                $renderer = new PhpRenderer();
                $resolver = new TemplatePathStack();
                $resolver->addPath(__DIR__ . '/../view/IvantageMail/templates');
                $renderer->setResolver($resolver);
                return $renderer;
            }
        )
    )
);
