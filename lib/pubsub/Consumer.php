<?php
namespace PubSub;

class Consumer extends Client
{
    private $maxMessagesPerPull;
    private $sleepTimeBetweenPulls;
    private $retryCount;
    private $retryDelay;
    private $flowControlEnabled;
    private $maxOutstandingMessages;
    private $maxOutstandingBytes;

    public function __construct(array $config)
    {
        parent::__construct($config);

        // Set consumer configuration
        $this->maxMessagesPerPull = $this->config['consumer']['max_messages_per_pull'];
        $this->sleepTimeBetweenPulls = $this->config['consumer']['sleep_time_between_pulls'];
        $this->retryCount = $this->config['consumer']['retry_count'];
        $this->retryDelay = $this->config['consumer']['retry_delay'];
        
        // Flow control configuration
        $this->flowControlEnabled = $this->config['consumer']['flow_control']['enabled'];
        $this->maxOutstandingMessages = $this->config['consumer']['flow_control']['max_outstanding_messages'];
        $this->maxOutstandingBytes = $this->config['consumer']['flow_control']['max_outstanding_bytes'];
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
                    // 'maxMessages' => $maxMessages,
                    // 'returnImmediately' => false
                ];

                $messages = $subscription->pull($options);
                $count = count($messages);
                if ($count === 0) {
                    $this->logger->debug("No messages pulled from subscription {$this->subscriptionName}");
                } else {
                    $this->logger->info("Pulled {$count} messages from subscription {$this->subscriptionName}");
                }
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
                $this->logger->debug("Acknowledged " . count($messages) . " messages");
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
     * @param callable $runner Function to process each message
     * @param int $maxMessages Maximum number of messages to process
     * @return int Number of processed messages
     */
    public function processMessages(callable $inspector, callable $runner, $maxMessages = null)
    {
        $maxMessages = $maxMessages ?: $this->maxMessagesPerPull;
        
        // Apply flow control if enabled
        if ($this->flowControlEnabled) {
            $outstandingMessages = $this->getOutstandingMessageCount();
            if ($outstandingMessages >= $this->maxOutstandingMessages) {
                $this->logger->warning("Flow control: Outstanding messages ({$outstandingMessages}) reached limit ({$this->maxOutstandingMessages}). Skipping pull.");
                return 0;
            }
            
            // Adjust maxMessages based on available capacity
            $availableCapacity = $this->maxOutstandingMessages - $outstandingMessages;
            $maxMessages = min($maxMessages, $availableCapacity);
        }
        
        $messages = $this->pull($maxMessages);
        $processedCount = 0;
        $failedMessages = [];
        $processingStartTime = microtime(true);

        if (empty($messages)) {
            $this->logger->debug("No messages to process.");
            return 0;
        }

        $this->logger->debug("Processing " . count($messages) . " messages...");

        $subscription = $this->pubSubClient->subscription($this->subscriptionName);
        $rawMessages = [];
        foreach ($messages as $message) {
            $rawMessages[$message->id()] = $message;
            $subscription->acknowledge($message);
        }

        // Inspeksi message sebelum diproses
        $this->logger->debug("Inpect Messages: " . print_r($rawMessages, true));
        $valid = $inspector($rawMessages);

        foreach ($valid as $messageId => $messageWrapper) {
            try {
                list($skrining, $message) = $messageWrapper;
                $messageData = $message->data();
                $attributes = $message->attributes();
                
                // Decompress message if needed
                if (!empty($attributes['compressed']) && $attributes['compressed'] === 'true') {
                    $messageData = $this->decompressMessage($messageData, $attributes['compression'] ?? 'gzip');
                }

                // jalankan callback runner
                $result = $runner($skrining, $message);

                if ($result === true) {
                    // Acknowledge the message if callback returns true
                    $processedCount++;
                    $this->logger->debug("Message processed and acknowledged. Data: {$messageData}");
                } else {
                    $failedMessages[] = $message;
                    $this->logger->warning("Message processing failed, not acknowledging. Data: {$messageData}");
                }

            } catch (\Exception $e) {
                $failedMessages[] = $message;
                $this->logger->error("Error processing message: " . $e->getMessage());
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
     * Decompress message data
     *
     * @param string $messageData
     * @param string $algorithm
     * @return string
     */
    private function decompressMessage($messageData, $algorithm = 'gzip')
    {
        if ($algorithm === 'gzip') {
            return gzdecode($messageData);
        }
        
        // Add other decompression algorithms as needed
        return $messageData;
    }

    /**
     * Get the count of outstanding messages
     *
     * @return int
     */
    private function getOutstandingMessageCount()
    {
        try {
            $subscription = $this->pubSubClient->subscription($this->subscriptionName);
            $info = $subscription->info();
            return $info['acknowledgementDeadlineCount'] ?? 0;
        } catch (\Exception $e) {
            $this->logger->warning("Failed to get outstanding message count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Listen for messages continuously with graceful shutdown
     *
     * @param callable $inspector Function to prepare all message before processing
     * @param callable $runner Function to process each message
     * @param int $sleepTime Seconds to sleep between pulls
     * @param int $maxMessages Maximum messages per pull
     */
    public function listen(callable $inspector, callable $runner, $sleepTime = null, $maxMessages = null)
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
            $this->logger->info("PCNTL extension not found. Graceful shutdown (Ctrl+C) is disabled.");
        }

        $totalProcessed = 0;
        $startTime = time();
        $lastLogTime = $startTime;

        while (true) {
            try {
                $processedCount = $this->processMessages($inspector, $runner, $maxMessages);
                $totalProcessed += $processedCount;

                // Log progress every minute
                $currentTime = time();
                if ($currentTime - $lastLogTime >= 60) {
                    $runtime = $currentTime - $startTime;
                    $rate = $runtime > 0 ? round($totalProcessed / $runtime, 2) : 0;
                    $this->logger->debug("Runtime: {$runtime}s, Total processed: {$totalProcessed}, Rate: {$rate} msg/s");
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
}
