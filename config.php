<?php

return [
    'google_cloud' => [
        'project_id' => getenv('GOOGLE_CLOUD_PROJECT') ?: 'gen-lang-client-0327718602',
        'credentials_path' => getenv('GOOGLE_APPLICATION_CREDENTIALS') ?: __DIR__ . '/credentials.json',
        'auth_scopes' => [
            'https://www.googleapis.com/auth/pubsub',
            'https://www.googleapis.com/auth/cloud-platform'
        ]
    ],
    'pubsub' => [
        'default_topic' => 'CKG-TB',
        'default_subscription' => 'ckg_tb',
        'topics' => [
            'CKG-TB' => [
                'subscription' => 'ckg_tb',
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
    'logging' => [
        'level' => 'INFO', // DEBUG, INFO, WARNING, ERROR
        'file' => __DIR__ . '/pubsub.log'
    ]
];