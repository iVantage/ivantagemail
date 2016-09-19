<?php

namespace IvantageMail\Entity;

interface EmailInterface {

    public function getId();

    public function getStatus();

    public function getTo();

    public function getFrom();

    public function getReplyTo();

    public function getSubject();

    public function getBody();

    public function getOptions();

    public function getRenderer();

    public function setId( $id );

    public function setStatus( $status );

    public function setTo( $to );

    public function setFrom( $from );

    public function setReplyTo( $replyTo );

    public function setSubject( $subject );

    public function setBody( $body );

    public function setOptions( $options );

    public function setRenderer( $renderer );

    public function toMessage();

    public function renderContent($tpl, array $data);

    public function renderBody($tpl, array $data);
}