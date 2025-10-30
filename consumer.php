<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';

use Google\Cloud\PubSub\PubSubClient;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class PubSubConsumer
{
    private $pubSubClient;
    private $topicName;
    private $subscriptionName;
    private $projectId;
    private $config;
    private $logger;
    private $maxMessagesPerPull;
    private $sleepTimeBetweenPulls;
    private $retryCount;
    private $retryDelay;

    public function __construct($projectId, $topicName, $subscriptionName, $config = null)
    {
        $this->projectId = $projectId;
        $this->topicName = $topicName;
        $this->subscriptionName = $subscriptionName;
        
        // Load configuration
        $this->config = $config ?: require __DIR__ . '/config.php';
        
        // Set consumer configuration
        $this->maxMessagesPerPull = $this->config['consumer']['max_messages_per_pull'];
        $this->sleepTimeBetweenPulls = $this->config['consumer']['sleep_time_between_pulls'];
        $this->retryCount = $this->config['consumer']['retry_count'];
        $this->retryDelay = $this->config['consumer']['retry_delay'];

        // Initialize logger
        $this->logger = new Logger('pubsub-consumer');
        $this->logger->pushHandler(new StreamHandler($this->config['logging']['file'], $this->config['logging']['level']));

        // Initialize Pub/Sub client with credentials
        $clientConfig = [
            'projectId' => $this->projectId
        ];
        
        $credentialsPath = $this->config['google_cloud']['credentials_path'];
        if (file_exists($credentialsPath)) {
            $jsonKey = json_decode(file_get_contents($credentialsPath), true);
            $clientConfig['keyFile'] = $jsonKey;
        }
        
        $this->pubSubClient = new PubSubClient($clientConfig);
        
        $this->logger->info("Consumer initialized for topic: {$topicName}, subscription: {$subscriptionName}");
    }

    /**
     * Pull messages from subscription with retry logic
     *
     * @param int $maxMessages Maximum number of messages to pull
     * @return array Array of messages
     */
    public function pull($maxMessages = null)
    {
        $maxMessages = $maxMessages ?: $this->maxMessagesPerPull;
        
        // Validate maxMessages
        if ($maxMessages <= 0 || $maxMessages > 1000) {
            $this->logger->warning("Invalid maxMessages value: {$maxMessages}. Using default: {$this->maxMessagesPerPull}");
            $maxMessages = $this->maxMessagesPerPull;
        }
        
        for ($attempt = 1; $attempt <= $this->retryCount; $attempt++) {
            try {
                $subscription = $this->pubSubClient->subscription($this->subscriptionName);
                $options = [
                    'maxMessages' => $maxMessages,
                    'returnImmediately' => false
                ];

                $result = $subscription->pull($options);
                $messages = [];

                foreach ($result as $pullMessage) {
                    $messages[] = $pullMessage;
                }

                $this->logger->info("Pulled " . count($messages) . " messages from subscription {$this->subscriptionName}");
                return $messages;

            } catch (\Google\Cloud\Core\Exception\GoogleException $e) {
                $this->logger->warning("Pull attempt {$attempt} failed: " . $e->getMessage());
                
                if ($attempt < $this->retryCount) {
                    $delay = $this->retryDelay * $attempt; // Linear backoff
                    sleep($delay);
                } else {
                    $this->logger->error("Failed to pull messages after {$this->retryCount} attempts");
                    return [];
                }
            } catch (\Exception $e) {
                $this->logger->error("Unexpected error during pull: " . $e->getMessage());
                return [];
            }
        }
        
        return [];
    }

    /**
     * Acknowledge received messages
     *
     * @param array $messages Messages to acknowledge
     * @return bool
     */
    public function acknowledge($messages)
    {
        for ($attempt = 1; $attempt <= $this->retryCount; $attempt++) {
            try {
                $subscription = $this->pubSubClient->subscription($this->subscriptionName);
                $subscription->acknowledgeBatch($messages);
                $this->logger->info("Acknowledged " . count($messages) . " messages");
                return true;

            } catch (\Exception $e) {
                $this->logger->warning("Acknowledge attempt {$attempt} failed: " . $e->getMessage());
                
                if ($attempt < $this->retryCount) {
                    sleep($this->retryDelay);
                } else {
                    $this->logger->error("Failed to acknowledge messages after {$this->retryCount} attempts");
                    return false;
                }
            }
        }
        
        return false;
    }

    /**
     * Process messages with callback function and error handling
     *
     * @param callable $callback Function to process each message
     * @param int $maxMessages Maximum number of messages to process
     * @return int Number of processed messages
     */
    public function processMessages(callable $callback, $maxMessages = null)
    {
        $maxMessages = $maxMessages ?: $this->maxMessagesPerPull;
        $messages = $this->pull($maxMessages);
        $processedCount = 0;
        $failedMessages = [];
        $processingStartTime = microtime(true);

        if (empty($messages)) {
            $this->logger->info("No messages to process.");
            return 0;
        }

        $this->logger->info("Processing " . count($messages) . " messages...");

        $subscription = $this->pubSubClient->subscription($this->subscriptionName);

        foreach ($messages as $message) {
            try {
                $messageData = $message->data();
                $attributes = $message->attributes();
                $ackId = $message->ackId();

                // Call the callback function
                $result = $callback($messageData, $attributes, $ackId);

                if ($result === true) {
                    // Acknowledge the message if callback returns true
                    $subscription->acknowledge($message);
                    $processedCount++;
                    $this->logger->debug("Message processed and acknowledged. Data: {$messageData}");
                } else {
                    $failedMessages[] = $message;
                    $this->logger->warning("Message processing failed, not acknowledging. Data: {$messageData}");
                }

            } catch (\Exception $e) {
                $failedMessages[] = $message;
                $this->logger->error("Error processing message: " . $e->getMessage());
                // Don't acknowledge failed messages so they can be retried
            }
        }

        // Acknowledge successfully processed messages in batch
        if ($processedCount > 0) {
            $success = $this->acknowledge(array_slice($messages, 0, $processedCount));
            if (!$success) {
                $this->logger->warning("Failed to acknowledge some processed messages");
            }
        }

        $processingTime = microtime(true) - $processingStartTime;
        $successRate = count($messages) > 0 ? ($processedCount / count($messages) * 100) : 0;
        
        $this->logger->info("Processed {$processedCount}/" . count($messages) . " messages ({$successRate}% success rate) in " . round($processingTime, 2) . "s");

        return $processedCount;
    }

    /**
     * Listen for messages continuously with graceful shutdown
     *
     * @param callable $callback Function to process each message
     * @param int $sleepTime Seconds to sleep between pulls
     * @param int $maxMessages Maximum messages per pull
     */
    public function listen(callable $callback, $sleepTime = null, $maxMessages = null)
    {
        $sleepTime = $sleepTime ?: $this->sleepTimeBetweenPulls;
        $maxMessages = $maxMessages ?: $this->maxMessagesPerPull;
        
        $this->logger->info("Starting message listener...");
        $this->logger->info("Listening to subscription: {$this->subscriptionName}");
        $this->logger->info("Sleep time: {$sleepTime}s, Max messages per pull: {$maxMessages}");
        $this->logger->info("Press Ctrl+C to stop");

        // Set up signal handlers for graceful shutdown if pcntl extension is available
        if (extension_loaded('pcntl')) {
            $this->logger->info("PCNTL extension found. Enabling graceful shutdown.");
            pcntl_async_signals(true);
            pcntl_signal(SIGTERM, [$this, 'handleSignal']);
            pcntl_signal(SIGINT, [$this, 'handleSignal']);
        } else {
            $this->logger->warning("PCNTL extension not found. Graceful shutdown (Ctrl+C) is disabled.");
        }

        $totalProcessed = 0;
        $startTime = time();
        $lastLogTime = $startTime;

        while (true) {
            try {
                $processedCount = $this->processMessages($callback, $maxMessages);
                $totalProcessed += $processedCount;

                // Log progress every minute
                $currentTime = time();
                if ($currentTime - $lastLogTime >= 60) {
                    $runtime = $currentTime - $startTime;
                    $rate = $runtime > 0 ? round($totalProcessed / $runtime, 2) : 0;
                    $this->logger->info("Runtime: {$runtime}s, Total processed: {$totalProcessed}, Rate: {$rate} msg/s");
                    $lastLogTime = $currentTime;
                }

                if ($processedCount === 0) {
                    $this->logger->debug("No messages received. Sleeping for {$sleepTime} seconds...");
                }

                sleep($sleepTime);

            } catch (\Exception $e) {
                $this->logger->error("Error in message loop: " . $e->getMessage());
                sleep($sleepTime);
            }
        }
    }

    /**
     * Handle shutdown signals
     *
     * @param int $signal
     */
    public function handleSignal($signal)
    {
        $this->logger->info("Received signal {$signal}. Shutting down gracefully...");
        exit(0);
    }

    /**
     * Get subscription info
     *
     * @return array Subscription information
     */
    public function getSubscriptionInfo()
    {
        try {
            $subscription = $this->pubSubClient->subscription($this->subscriptionName);

            if ($subscription->exists()) {
                $subscriptionInfo = $subscription->info();
                $info = [
                    'name' => $subscription->name(),
                    'topic' => $subscriptionInfo['topic'],
                    'exists' => true,
                    'messageRetentionDuration' => $subscriptionInfo['messageRetentionDuration'] ?? null
                ];
                
                $this->logger->info("Subscription info retrieved: " . json_encode($info));
                return $info;
            } else {
                $this->logger->warning("Subscription {$this->subscriptionName} does not exist");
                return [
                    'name' => $subscription->name(),
                    'exists' => false
                ];
            }

        } catch (\Exception $e) {
            $this->logger->error("Error getting subscription info: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create a subscription if it doesn't exist
     *
     * @param string $topicName
     * @param string $subscriptionName
     * @param bool $enableMessageOrdering
     * @return bool
     */
    public function ensureSubscriptionExists($topicName, $subscriptionName, $enableMessageOrdering = false)
    {
        try {
            $topic = $this->pubSubClient->topic($topicName);
            
            if (!$topic->exists()) {
                $topic->create();
                $this->logger->info("Topic '{$topicName}' created successfully.");
            }

            $subscription = $this->pubSubClient->subscription($subscriptionName);

            if (!$subscription->exists()) {
                $subscription->create($topic, [
                    'enableMessageOrdering' => $enableMessageOrdering
                ]);
                $this->logger->info("Subscription '{$subscriptionName}' created successfully.");
            } else {
                $this->logger->info("Subscription '{$subscriptionName}' already exists.");
            }

            return true;

        } catch (\Exception $e) {
            $this->logger->error("Error ensuring subscription exists: " . $e->getMessage());
            return false;
        }
    }
}

// CLI usage
if (php_sapi_name() === 'cli') {
    $config = require __DIR__ . '/config.php';
    
    $projectId = getenv('GOOGLE_CLOUD_PROJECT') ?: $config['google_cloud']['project_id'];
    $topicName = $argv[1] ?? $config['pubsub']['default_topic'];
    $subscriptionName = $argv[2] ?? $config['pubsub']['topics'][$topicName]['subscription'] ?? $config['pubsub']['default_subscription'];
    
    // Create consumer instance
    $consumer = new PubSubConsumer($projectId, $topicName, $subscriptionName, $config);

    // Ensure subscription exists
    $enableOrdering = !empty($config['pubsub']['topics'][$topicName]['message_ordering']);
    $consumer->ensureSubscriptionExists($topicName, $subscriptionName, $enableOrdering);

    // Show subscription info
    $info = $consumer->getSubscriptionInfo();
    echo "Subscription Info:\n";
    print_r($info);

    // Start continuous listener
    echo "\n--- Starting continuous listener (press Ctrl+C to stop) ---\n";
    $consumer->listen(function($data, $attributes, $ackId) {
        echo "Received message at " . date('Y-m-d H:i:s') . ":\n";
        echo "  Data: {$data}\n";
        echo "  Attributes: " . json_encode($attributes) . "\n";

        // Simulate message processing
        sleep(1);

        // Return true to acknowledge, false to leave for retry
        return true;
    });
}