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
        'default_subscription' => 'projects/ckg-tb-staging/subscriptions/CKG-SITB-sub2',
        'topics' => [
            'projects/ckg-tb-staging/topics/CKG-SITB' => [
                'subscription' => 'projects/ckg-tb-staging/subscriptions/CKG-SITB-sub2',
                'message_ordering' => false
            ],
        ]
    ],
    'consumer' => [
        'max_messages_per_pull' => 10,
        'sleep_time_between_pulls' => 5,
        'acknowledge_timeout' => 60, // seconds
        'retry_count' => 3,
        'retry_delay' => 1, // seconds
        'flow_control' => [
            'enabled' => false,
            'max_outstanding_messages' => 1000,
            'max_outstanding_bytes' => 1000000 // 1MB
        ]
    ],
    'producer' => [
        'enable_message_ordering' => false,
        'batch_size' => 100,
        'message_attributes' => [
            'source' => 'sitb-pubsub-client',
            'version' => '1.0.0'
        ],
        'compression' => [
            'enabled' => false,
            'algorithm' => 'gzip'
        ]
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
        'level' => 'DEBUG', // DEBUG, INFO, WARNING, ERROR
        'consumer' => '/var/log/sitb-ckg/consumer.log',
        'producer-pubsub' => '/var/log/sitb-ckg/producer-pubsub.log',
        'producer-api' => '/var/log/sitb-ckg/producer-api.log',
    ],
    'ckg' => [
        'table_skrining' => 'ta_skrining',
        'table_laporan_so' => 'lapso',
        'table_laporan_ro' => 'lapro',
        'table_incoming' => 'tmp_ckg_incoming',
        'table_outgoing' => 'tmp_ckg_outgoing',
        'table_processed' => 'tmp_ckg_processed',
        'marker_field' => 'transactionSource',
        'marker_produce' => 'STATUS-PASIEN-TB',
		'marker_consume' => 'SKRINING-CKG-TB',
    ]
];