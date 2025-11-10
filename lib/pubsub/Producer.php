<?php
namespace PubSub;

class Producer extends Client
{
    private $enableMessageOrdering;
    private $batchSize;
    private $defaultAttributes;
    private $compressionEnabled;
    private $compressionAlgorithm;

    public function __construct(array $config)
    {
        parent::__construct($config);

        // Set producer configuration
        $this->enableMessageOrdering = $this->config['producer']['enable_message_ordering'];
        $this->batchSize = $this->config['producer']['batch_size'];
        $this->defaultAttributes = $this->config['producer']['message_attributes'];
        $this->compressionEnabled = $this->config['producer']['compression']['enabled'];
        $this->compressionAlgorithm = $this->config['producer']['compression']['algorithm'];
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
            $this->logger->warning("Message data cannot be empty");
            return false;
        }

        // Merge with default attributes
        $finalAttributes = array_merge($this->defaultAttributes, $attributes, ['environment' => $this->config['environment']]);
        
        // Add timestamp if not provided
        if (!isset($finalAttributes['timestamp'])) {
            $finalAttributes['timestamp'] = (string) time();
        }

        // Apply compression if enabled
        if ($this->compressionEnabled) {
            $messageData = $this->compressMessage($messageData);
            $finalAttributes['compressed'] = 'true';
            $finalAttributes['compression'] = $this->compressionAlgorithm;
        }

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $topic = $this->pubSubClient->topic($this->topicName);

                // Prepare message with ordering if enabled
                $publishOptions = [
                    'data' => $messageData,
                    'attributes' => $finalAttributes
                ];

                // Add ordering key if message ordering is enabled
                if ($this->enableMessageOrdering && !empty($finalAttributes['ordering_key'])) {
                    $publishOptions['orderingKey'] = $finalAttributes['ordering_key'];
                }

                // Publish the message
                $message = $topic->publish($publishOptions);

                // $messageId = $message['messageId'];
                $messageStr = json_encode($message);
                $this->logger->info("Message published successfully. Message ID: {$messageStr}, Data: {$messageData}");
                
                return true;

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
     * Compress message data
     *
     * @param string $messageData
     * @return string
     */
    private function compressMessage($messageData)
    {
        if ($this->compressionAlgorithm === 'gzip') {
            return gzencode($messageData);
        }
        
        // Add other compression algorithms as needed
        return $messageData;
    }

    /**
     * Set custom attributes for a message
     *
     * @param array $attributes
     * @return void
     */
    public function setDefaultAttributes(array $attributes)
    {
        $this->defaultAttributes = array_merge($this->defaultAttributes, $attributes);
    }

    /**
     * Enable or disable message compression
     *
     * @param bool $enabled
     * @return void
     */
    public function setCompressionEnabled($enabled)
    {
        $this->compressionEnabled = $enabled;
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
            $this->logger->debug("Messages must be a non-empty array");
            return [];
        }

        $messageIds = [];
        $batches = array_chunk($messages, $this->batchSize);
        $assoc = is_array($attributes) && array_keys($attributes) !== range(0, count($attributes) - 1);
        
        $this->logger->info("Publishing " . count($messages) . " messages in " . count($batches) . " batches");

        foreach ($batches as $batchIndex => $batchMessages) {
            $batchMessageIds = [];
            $batchStartTime = microtime(true);
            
            foreach ($batchMessages as $index => $messageData) {
                $messageAttr = $assoc ? $attributes : (isset($attributes[$index]) ? $attributes[$index] : []);
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
}