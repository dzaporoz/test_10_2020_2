<?php

return [

    '' => [
        'controller' => 'post',
        'action' => 'list',
    ],

    'posts/(\d+)' => [
        'controller' => 'post',
        'action' => 'show',
    ],
];