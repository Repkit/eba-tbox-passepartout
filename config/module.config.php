<?php
return [
    'service_manager' => [
        'factories' => [
            // \TBoxPassepartout\Strategy\Strategy::class => \TBoxRabbitMQ\Strategy\ApigilityFactory::class,
        ],
    ],
    \TBoxPassepartout\Gatekeeper::CONFIG_KEY => [
        'head' => [
    		'user_agent' => 'Eba-Passepartout',
    		'auth' => 'x-auth-passepartout',
    		'originator' =>'x-eba-microservice-originator',
    		'target' =>'x-eba-microservice-target',
    		'identity' =>'x-eba-microservice-identity',
    	],
        'key' => [
        	'secret' => '<your_secret_key>',
        	'ttl' => 5, // hash time to live in minutes
        	//'rotation_interval' => 30, //minutes
        ],
        'whitelist' => null, // empty to allow from all IPs | regex '/127.0.0.1/'
    ]
];