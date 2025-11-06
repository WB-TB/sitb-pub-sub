<?php

return [
    'environment' => getenv('APP_ENV') ?: 'development',
    'google_cloud' => [
        'project_id' => getenv('GOOGLE_CLOUD_PROJECT') ?: 'ckg-tb-staging',
        'credentials_path' => getenv('GOOGLE_APPLICATION_CREDENTIALS') ?: __DIR__ . '/credentials.json',
        'debug' => getenv('GOOGLE_SDK_PHP_LOGGING') === 'true' ? true : false,
    ],
    'pubsub' => [
        'default_topic' => 'projects/ckg-tb-staging/topics/CKG-SITB',
        'default_subscription' => 'projects/ckg-tb-staging/subscriptions/CKG-SITB-sub',
        'topics' => [
            'projects/ckg-tb-staging/topics/CKG-SITB' => [
                'subscription' => 'projects/ckg-tb-staging/subscriptions/CKG-SITB-sub',
                'message_ordering' => false
            ],
        ]
    ],
    'consumer' => [
        'max_messages_per_pull' => 10,
        'sleep_time_between_pulls' => 5,
        'acknowledge_timeout' => 60, // seconds
        'retry_count' => 3,
        'retry_delay' => 1 // seconds
    ],
    'producer' => [
        'enable_message_ordering' => false,
        'batch_size' => 100
    ],
    'api' => [
        'base_url' => 'https://api-dev.dto.kemkes.go.id/fhir-sirs',
        'timeout' => 60, // seconds
        'api_key' => getenv('SITB_API_KEY') ?: 'your_api_key_here',
        'api_header' => 'X-API-Key:',
        'batch_size' => 100
    ],
    'database' => [
        'host' => 'mysql_service',
        'port' => 3306,
        'username' => 'xtb',
        'password' => 'xtb',
        'database_name' => 'xtb'
    ],
    'logging' => [
        'level' => 'INFO', // DEBUG, INFO, WARNING, ERROR
        'consumer' => '/var/log/sitb-ckg/consumer.log',
        'producer' => '/var/log/sitb-ckg/producer.log',
    ],
    'ckg' => [
        'table_skrining' => 'skrining_tb',
        'table_incoming' => 'ckg_pubsub_incoming',
        'table_outgoing' => 'ckg_pubsub_outgoing',
        'table_processed' => 'ckg_pubsub_processed',
        'marker_field' => 'transactionSource',
        'marker_value' => 'CKG-SITB',
    ]
];