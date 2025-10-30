<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';

use Google\Cloud\PubSub\PubSubClient;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class PubSubProducer
{
    private $pubSubClient;
    private $topicName;
    private $subscriptionName;
    private $projectId;
    private $config;
    private $logger;
    private $enableMessageOrdering;
    private $batchSize;

    public function __construct($projectId, $topicName, $subscriptionName = null, $config = null)
    {
        $this->projectId = $projectId;
        $this->topicName = $topicName;
        $this->subscriptionName = $subscriptionName;
        
        // Load configuration
        $this->config = $config ?: require __DIR__ . '/config.php';
        
        // Set producer configuration
        $this->enableMessageOrdering = $this->config['producer']['enable_message_ordering'];
        $this->batchSize = $this->config['producer']['batch_size'];

        // Initialize logger
        $this->logger = new Logger('pubsub-producer');
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
        
        $this->logger->info("Producer initialized for topic: {$topicName}");
    }

    /**
     * Ensure topic exists
     *
     * @return bool
     */
    private function ensureTopicExists()
    {
        try {
            $topic = $this->pubSubClient->topic($this->topicName);

            if (!$topic->exists()) {
                $topic->create();
                $this->logger->info("Topic '{$this->topicName}' created successfully.");
                return true;
            }
            
            return true;

        } catch (\Exception $e) {
            $this->logger->error("Error ensuring topic exists: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Publish a message to a topic with retry logic
     *
     * @param string $messageData The message content
     * @param array $attributes Optional message attributes
     * @return string|false Message ID or false on failure
     */
    public function publish($messageData, $attributes = [])
    {
        if (!$this->ensureTopicExists()) {
            return false;
        }

        // Validate message data
        if (empty($messageData)) {
            $this->logger->error("Message data cannot be empty");
            return false;
        }

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $topic = $this->pubSubClient->topic($this->topicName);

                // Prepare message with ordering if enabled
                $publishOptions = [
                    'data' => $messageData,
                    'attributes' => $attributes
                ];

                // Add ordering key if message ordering is enabled
                if ($this->enableMessageOrdering && !empty($attributes['ordering_key'])) {
                    $publishOptions['orderingKey'] = $attributes['ordering_key'];
                }

                // Publish the message
                $message = $topic->publish($publishOptions);

                $messageId = $message['messageId'];
                $this->logger->info("Message published successfully. Message ID: {$messageId}, Data: {$messageData}");
                
                return $messageId;

            } catch (\Google\Cloud\Core\Exception\GoogleException $e) {
                $this->logger->warning("Publish attempt {$attempt} failed: " . $e->getMessage());
                
                if ($attempt < 3) {
                    $delay = $attempt * 2; // Exponential backoff
                    sleep($delay);
                } else {
                    $this->logger->error("Failed to publish message after 3 attempts");
                    return false;
                }
            } catch (\Exception $e) {
                $this->logger->error("Unexpected error during publish: " . $e->getMessage());
                return false;
            }
        }
        
        return false;
    }

    /**
     * Publish multiple messages in batch with retry logic
     *
     * @param array $messages Array of message data
     * @param array $attributes Optional array of attributes for each message
     * @return array Array of message IDs
     */
    public function publishBatch($messages, $attributes = [])
    {
        // Validate input
        if (!is_array($messages) || empty($messages)) {
            $this->logger->error("Messages must be a non-empty array");
            return [];
        }

        $messageIds = [];
        $batches = array_chunk($messages, $this->batchSize);
        
        $this->logger->info("Publishing " . count($messages) . " messages in " . count($batches) . " batches");

        foreach ($batches as $batchIndex => $batchMessages) {
            $batchMessageIds = [];
            $batchStartTime = microtime(true);
            
            foreach ($batchMessages as $index => $messageData) {
                $messageAttr = isset($attributes[$index]) ? $attributes[$index] : [];
                $messageId = $this->publish($messageData, $messageAttr);
                
                if ($messageId !== false) {
                    $batchMessageIds[] = $messageId;
                } else {
                    $this->logger->warning("Failed to publish message at index {$index}");
                }
            }
            
            $messageIds = array_merge($messageIds, $batchMessageIds);
            
            // Add delay between batches to avoid rate limiting
            if ($batchIndex < count($batches) - 1) {
                $batchDuration = microtime(true) - $batchStartTime;
                $delay = max(0, 0.1 - $batchDuration); // Ensure at least 100ms between batches
                usleep($delay * 1000000);
            }
        }

        $successRate = count($messageIds) / count($messages) * 100;
        $this->logger->info("Published " . count($messageIds) . " out of " . count($messages) . " messages ({$successRate}% success rate)");
        return $messageIds;
    }

    /**
     * Create a subscription for this topic
     *
     * @param string $subscriptionName
     * @param bool $enableMessageOrdering
     * @return bool
     */
    public function createSubscription($subscriptionName, $enableMessageOrdering = null)
    {
        $enableMessageOrdering = $enableMessageOrdering ?? $this->enableMessageOrdering;
        
        try {
            if (!$this->ensureTopicExists()) {
                return false;
            }

            $topic = $this->pubSubClient->topic($this->topicName);

            // Create subscription if it doesn't exist
            $subscription = $this->pubSubClient->subscription($subscriptionName);

            if (!$subscription->exists()) {
                $subscription->create($topic, [
                    'enableMessageOrdering' => $enableMessageOrdering
                ]);
                $this->logger->info("Subscription '{$subscriptionName}' created successfully with message ordering: " . ($enableMessageOrdering ? 'enabled' : 'disabled'));
            } else {
                $this->logger->info("Subscription '{$subscriptionName}' already exists.");
            }

            return true;

        } catch (\Exception $e) {
            $this->logger->error("Error creating subscription: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get topic info
     *
     * @return array Topic information
     */
    public function getTopicInfo()
    {
        try {
            $topic = $this->pubSubClient->topic($this->topicName);

            if ($topic->exists()) {
                $info = [
                    'name' => $topic->name(),
                    'exists' => true,
                    'publishConfig' => $topic->info()['publishConfig'] ?? null
                ];
                
                $this->logger->info("Topic info retrieved: " . json_encode($info));
                return $info;
            } else {
                $this->logger->warning("Topic {$this->topicName} does not exist");
                return [
                    'name' => $topic->name(),
                    'exists' => false
                ];
            }

        } catch (\Exception $e) {
            $this->logger->error("Error getting topic info: " . $e->getMessage());
            return [];
        }
    }

    /**
     * List all subscriptions for this topic
     *
     * @return array List of subscription names
     */
    public function listSubscriptions()
    {
        try {
            $topic = $this->pubSubClient->topic($this->topicName);
            $subscriptions = [];
            
            foreach ($topic->subscriptions() as $subscription) {
                $subscriptions[] = $subscription->name();
            }
            
            $this->logger->info("Found " . count($subscriptions) . " subscriptions for topic {$this->topicName}");
            return $subscriptions;

        } catch (\Exception $e) {
            $this->logger->error("Error listing subscriptions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete a subscription
     *
     * @param string $subscriptionName
     * @return bool
     */
    public function deleteSubscription($subscriptionName)
    {
        try {
            $subscription = $this->pubSubClient->subscription($subscriptionName);
            
            if ($subscription->exists()) {
                $subscription->delete();
                $this->logger->info("Subscription '{$subscriptionName}' deleted successfully.");
                return true;
            } else {
                $this->logger->warning("Subscription '{$subscriptionName}' does not exist.");
                return false;
            }

        } catch (\Exception $e) {
            $this->logger->error("Error deleting subscription: " . $e->getMessage());
            return false;
        }
    }
}

// CLI usage
if (php_sapi_name() === 'cli') {
    $config = require __DIR__ . '/config.php';
    
    $projectId = getenv('GOOGLE_CLOUD_PROJECT') ?: $config['google_cloud']['project_id'];
    $topicName = $argv[1] ?? $config['pubsub']['default_topic'];
    
    // Create producer instance
    $producer = new PubSubProducer($projectId, $topicName, null, $config);

    // Show topic info
    $info = $producer->getTopicInfo();
    echo "Topic Info:\n";
    print_r($info);

    // Create subscription for the topic
    $enableOrdering = !empty($config['pubsub']['topics'][$topicName]['message_ordering']);
    $producer->createSubscription($config['pubsub']['topics'][$topicName]['subscription'] ?? $config['pubsub']['default_subscription'], $enableOrdering);

    // List subscriptions
    echo "\nSubscriptions for topic {$topicName}:\n";
    $subscriptions = $producer->listSubscriptions();
    print_r($subscriptions);

    // Publish a single message
    echo "\nPublishing single message...\n";
    $messageId = $producer->publish('[{"terduga_id":"123","pasien_nik":"3403011703850005","pasien_tb_id":"123-ab","status_diagnosa":"TBCSO","diagnosa_lab_metode":"TCM","diagnosa_lab_hasil":"rif-sen","tanggal_mulai_pengobatan":null,"tanggal_selesai_pengobatan":null,"hasil_akhir":null}]', [
        'source' => 'producer-script',
        'timestamp' => (string) time(),
        'environment' => 'cli'
    ]);

    if ($messageId) {
        echo "Message published with ID: {$messageId}\n";
    } else {
        echo "Failed to publish message\n";
    }

    // Publish multiple messages
   /* echo "\nPublishing batch messages...\n";
    $messages = [
        'Message 1: Hello World',
        'Message 2: How are you?',
        'Message 3: Goodbye!',
        'Message 4: PHP PubSub is awesome!',
        'Message 5: Have a great day!'
    ];

    $attributes = [
        ['type' => 'greeting', 'priority' => 'high', 'source' => 'producer-script', 'timestamp' => (string) time()],
        ['type' => 'question', 'priority' => 'medium', 'source' => 'producer-script', 'timestamp' => (string) time()],
        ['type' => 'farewell', 'priority' => 'low', 'source' => 'producer-script', 'timestamp' => (string) time()],
        ['type' => 'info', 'priority' => 'medium', 'source' => 'producer-script', 'timestamp' => (string) time()],
        ['type' => 'greeting', 'priority' => 'medium', 'source' => 'producer-script', 'timestamp' => (string) time()]
    ];*/

    $messageIds = $producer->publishBatch($messages, $attributes);
    
    if (!empty($messageIds)) {
        echo "\nSuccessfully published " . count($messageIds) . " messages!\n";
    } else {
        echo "\nFailed to publish batch messages\n";
    }
}