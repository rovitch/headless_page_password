<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Headless Page Password',
    'description' => 'Headless compatibility for page_password',
    'state' => 'stable',
    'author' => '',
    'author_email' => '',
    'category' => 'fe',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
            'page_password' => '1.0.0-1.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
