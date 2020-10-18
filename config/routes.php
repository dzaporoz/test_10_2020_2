<?php

return [

    '' => [
        'controller' => 'article',
        'action' => 'list',
    ],

    'articles/(\d+)' => [
        'controller' => 'article',
        'action' => 'show',
    ],
];