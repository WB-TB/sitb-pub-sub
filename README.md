# sitb-pub-sub
Integrasi data SITB dan ASIK CKG melalui Google Pub/Sub dan API

## Overview

This is a PHP implementation for integrating SIBT data with Google Cloud Pub/Sub. The implementation includes:

- **Producer**: Publish messages to Google Pub/Sub topics
- **Consumer**: Subscribe and process messages from Google Pub/Sub subscriptions
- **Client**: Base class with common functionality for both producer and consumer

## Features

- **Message Compression**: Optional gzip compression for large messages
- **Dead Letter Queue**: Automatic handling of failed messages
- **Flow Control**: Configurable limits on outstanding messages
- **Message Ordering**: Support for ordered message delivery
- **Batch Processing**: Efficient batch publishing and processing
- **Retry Logic**: Configurable retry mechanisms with exponential backoff
- **Comprehensive Logging**: Detailed logging for debugging and monitoring

## Installation

1. Install dependencies:
```bash
composer install
```

2. Set up Google Cloud credentials:
   - Create a service account in Google Cloud Console
   - Download the credentials JSON file
   - Place it in the project directory as `credentials.json`

## Configuration

Edit `config.php` to match your Google Cloud project settings:

```php
return [
    'google_cloud' => [
        'project_id' => 'your-project-id',
        'credentials_path' => __DIR__ . '/credentials.json',
        'auth_scopes' => [
            'https://www.googleapis.com/auth/pubsub',
            'https://www.googleapis.com/auth/cloud-platform'
        ],
        'debug' => false
    ],
    'pubsub' => [
        'default_topic' => 'test-topic',
        'default_subscription' => 'test-subscription',
        'topics' => [
            'test-topic' => [
                'subscription' => 'test-subscription',
                'message_ordering' => false
            ]
        ]
    ],
    'consumer' => [
        'max_messages_per_pull' => 10,
        'sleep_time_between_pulls' => 5,
        'acknowledge_timeout' => 60,
        'retry_count' => 3,
        'retry_delay' => 1,
        'dead_letter_policy' => [
            'enabled' => true,
            'max_delivery_attempts' => 5,
            'dead_letter_topic_suffix' => '-dead-letter'
        ],
        'flow_control' => [
            'enabled' => true,
            'max_outstanding_messages' => 1000,
            'max_outstanding_bytes' => 1000000 // 1MB
        ]
    ],
    'producer' => [
        'enable_message_ordering' => false,
        'batch_size' => 100,
        'message_attributes' => [
            'source' => 'php-pubsub-client',
            'version' => '1.0.0',
            'environment' => 'development'
        ],
        'compression' => [
            'enabled' => false,
            'algorithm' => 'gzip'
        ]
    ],
    'logging' => [
        'level' => 'INFO',
        'file' => __DIR__ . '/pubsub.log'
    ]
];
```

## Usage

### Producer

```php
<?php
require __DIR__ . '/lib/Boot.php';

$config = Boot::getConfig();
$producer = new \PubSub\Producer($config);

// Publish a single message
$messageId = $producer->publish('Hello, World!', [
    'source' => 'my-app',
    'timestamp' => time()
]);

if ($messageId) {
    echo "Message published with ID: {$messageId}\n";
}

// Publish multiple messages in batch
$messages = [
    'Message 1: Hello World',
    'Message 2: How are you?',
    'Message 3: Goodbye!'
];

$attributes = [
    ['type' => 'greeting', 'priority' => 'high'],
    ['type' => 'question', 'priority' => 'medium'],
    ['type' => 'farewell', 'priority' => 'low']
];

$messageIds = $producer->publishBatch($messages, $attributes);

echo "Published " . count($messageIds) . " messages\n";
```

### Consumer

```php
<?php
require __DIR__ . '/lib/Boot.php';

$config = Boot::getConfig();
$consumer = new \PubSub\Consumer($config);

// Process messages once
$consumer->processMessages(
    function($messages) {
        // Inspector function - filter/validate messages
        return $messages; // Return messages to process
    },
    function($skrining, $message, $skriningId) {
        // Process each message
        $data = $message->data();
        echo "Processing message: {$data}\n";
        
        // Return true to acknowledge, false to retry
        return true;
    }
);

// Listen for messages continuously
$consumer->listen(
    function($messages) {
        // Inspector function
        return $messages;
    },
    function($skrining, $message, $skriningId) {
        // Process each message
        $data = $message->data();
        echo "Received message: {$data}\n";
        return true;
    }
);
```

## CLI Usage

### Producer CLI

```bash
php producer.php --mode=pubsub
```

### Consumer CLI

```bash
php consumer.php
```

## Advanced Features

### Dead Letter Queue

When enabled, failed messages are automatically moved to a dead letter topic with the suffix `-dead-letter`. This allows for manual inspection and reprocessing of failed messages.

### Flow Control

The consumer implements flow control to prevent overwhelming the system with too many outstanding messages. When the limit is reached, the consumer will skip pulling new messages until capacity becomes available.

### Message Compression

Large messages can be compressed using gzip to reduce bandwidth usage. The compression is automatically handled by the producer and decompressed by the consumer.

### Message Ordering

For ordered message delivery, enable message ordering in the configuration and provide an ordering key in the message attributes.

## Error Handling

The implementation includes comprehensive error handling with:

- Retry logic with exponential backoff
- Detailed logging of all operations
- Graceful handling of Google Cloud API errors
- Automatic dead letter queue for failed messages

## Monitoring

All operations are logged to `pubsub.log` with configurable log levels. The logs include:

- Message publish/acknowledge status
- Processing statistics and success rates
- Error details and retry attempts
- Performance metrics

## Testing

Run the test suite:

```bash
composer test
```

### Unit Tests

The project includes unit tests for all major components:

- **Producer Tests**: Test message publishing, batch processing, and compression
- **Consumer Tests**: Test message consumption, acknowledgment, and dead letter handling
- **Client Tests**: Test base functionality and Google Cloud integration

To run specific test suites:

```bash
# Run producer tests
./vendor/bin/phpunit tests/ProducerTest.php

# Run consumer tests
./vendor/bin/phpunit tests/ConsumerTest.php

# Run client tests
./vendor/bin/phpunit tests/ClientTest.php
```

### Mock Testing

For testing without actual Google Cloud services, the project includes mock implementations:

```php
// Use mock client for testing
$mockClient = new MockPubSubClient();
$producer = new \PubSub\Producer($config, $mockClient);
```

## License

This project is licensed under the MIT License.
