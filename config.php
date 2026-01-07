<?php

return [
    'environment' => getenv('APP_ENV') ? getenv('APP_ENV') : 'development',
    'producer_mode' => 'api', // 'pubsub' or 'api'
    'google_cloud' => [
        'project_id' => getenv('GOOGLE_CLOUD_PROJECT') ? getenv('GOOGLE_CLOUD_PROJECT') : 'ckg-tb-staging', // <-- BUTUH DIUPDATE
        'credentials_path' => getenv('GOOGLE_APPLICATION_CREDENTIALS') ? getenv('GOOGLE_APPLICATION_CREDENTIALS') : __DIR__ . '/credentials.json',
        'debug' => getenv('GOOGLE_SDK_PHP_LOGGING') === 'true' ? true : false,
    ],
    'pubsub' => [
        'default_topic' => 'projects/ckg-tb-staging/topics/CKG-SITB',            // <-- BUTUH DIUPDATE
        'default_subscription' => 'projects/ckg-tb-staging/subscriptions/Dev',   // <-- BUTUH DIUPDATE
        'topics' => [
            'projects/ckg-tb-staging/topics/CKG-SITB' => [
                'subscription' => 'projects/ckg-tb-staging/subscriptions/Dev',
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
        'base_url' => 'https://api-dev.dto.kemkes.go.id/fhir-sirs', // <-- BUTUH DIUPDATE
        'timeout' => 60, // seconds
        'api_key' => getenv('SITB_API_KEY') ?: 'your_api_key_here', // <-- BUTUH DIUPDATE
        'api_header' => 'X-API-Key:',
        'batch_size' => 100
    ],
    'database' => [
        'host' => 'mysql_service',                                  // <-- BUTUH DIUPDATE
        'port' => 3306,                                             // <-- BUTUH DIUPDATE
        'username' => 'xtb',                                        // <-- BUTUH DIUPDATE
        'password' => 'xtb',                                        // <-- BUTUH DIUPDATE
        'database_name' => 'xtb'                                    // <-- BUTUH DIUPDATE
    ],
    'logging' => [
        'level' => 'DEBUG', // DEBUG, INFO, WARNING, ERROR
        'consumer' => '/var/log/sitb-ckg/consumer.log',
        'producer-pubsub' => '/var/log/sitb-ckg/producer-pubsub.log',
        'producer-api' => '/var/log/sitb-ckg/producer-api.log',
    ],
    'ckg' => [
        'table_skrining' => 'ta_skrining',                         // <-- BUTUH DIUPDATE
        'table_laporan_so' => 'lap_tbc_03so',                      // <-- BUTUH DIUPDATE nama tabel laporan SO
        'table_laporan_ro' => 'lap_tbc_03ro',                      // <-- BUTUH DIUPDATE nama tabel laporan RO
        'table_incoming' => 'ckg_pubsub_incoming',
        'table_outgoing' => 'ckg_pubsub_outgoing',
        'marker_field' => 'transactionSource',
        'marker_produce' => 'STATUS-PASIEN-TB',
		'marker_consume' => 'SKRINING-CKG-TB',
    ]
];