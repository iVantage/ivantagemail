<?php

return array(
    'modules' => array(
        'DoctrineModule',
        'DoctrineORMModule'
    ),
    'module_listener_options' => array(
        'config_glob_paths'    => array(
            __DIR__ . '/test.config.php',
            __DIR__ . '/../config/module.config.php',
        ),
        'module_paths' => array(
        ),
    )
);
