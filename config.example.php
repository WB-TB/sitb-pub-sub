<?php

return [
    'environment' => 'development',
    'producer_mode' => 'api', // 'pubsub' or 'api'
    'google_cloud' => [
        'project_id' => 'dto-ihs-dev',                                       // <-- BUTUH DIUPDATE
        'credentials_path' => __DIR__ . '/credentials.json',
        'debug' => false,
    ],
    'pubsub' => [
        'default_topic' => 'projects/dto-ihs-dev/topics/pkg-konsolidator-tb',            // <-- BUTUH DIUPDATE
        'default_subscription' => 'projects/dto-ihs-dev/subscriptions/pkg-konsolidator-tb-sub',   // <-- BUTUH DIUPDATE
        'topics' => [
            'projects/dto-ihs-dev/topics/pkg-konsolidator-tb' => [
                'subscription' => 'projects/dto-ihs-dev/subscriptions/pkg-konsolidator-tb-sub',
                'message_ordering' => false
            ],
        ],
        'database' => __DIR__ . '/pubsub.sqlite',
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
        'base_url' => 'https://api.sspkg.appgo.my.id/api', // <-- BUTUH DIUPDATE
        'timeout' => 60, // seconds
        'api_key' => 'ovI6zk4FcnU9e2h0',                           // <-- BUTUH DIUPDATE
        'api_header' => 'X-API-Key:',
        'batch_size' => 100
    ],
    'database' => [
        'host' => 'mysql_service',                                  // <-- BUTUH DIUPDATE
        'port' => 3306,                                             // <-- BUTUH DIUPDATE
        'username' => 'xtb',                                        // <-- BUTUH DIUPDATE
        'password' => 'xtb',                                        // <-- BUTUH DIUPDATE
        'database_name' => 'xtb',                                   // <-- BUTUH DIUPDATE
        'timezone' => '+07:00'                                      // <-- Timezone untuk kolom datetime (UTC+07 = WIB)
    ],
    'logging' => [
        'level' => 'DEBUG', // DEBUG, INFO, WARNING, ERROR
        'consumer' => '/var/log/sitb-ckg/consumer.log',
        'producer-pubsub' => '/var/log/sitb-ckg/producer-pubsub.log',
        'producer-api' => '/var/log/sitb-ckg/producer-api.log',
    ],
    'ckg' => [
        'table_skrining' => 'ta_skrining',                          // <-- CEK SUDAH SESUAI
        'table_laporan_so' => 'lap_tbc_03so',                       // <-- CEK SUDAH SESUAI nama tabel laporan SO
        'table_laporan_ro' => 'lap_tbc_03ro',                       // <-- CEK SUDAH SESUAI nama tabel laporan RO
        'table_incoming' => 'ckg_pubsub_incoming',
        'table_outgoing' => 'ckg_pubsub_outgoing',
        'marker_field' => 'transactionSource',
        'marker_produce' => 'STATUS-PASIEN-TB',
		'marker_consume' => 'SKRINING-CKG-TB',
    ]
];