<?php

return [
    'enabled' => [
        'Admin',
        'Auth',
        'Articles',
        'Customers'
    ],
    
    'auto_discovery' => true,
    
    'cache' => [
        'enabled' => false,
        'key' => 'modules.cache',
        'lifetime' => 60
    ]
];