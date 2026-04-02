<?php

namespace PubSub;

use Exception;
use Google\Cloud\PubSub\PubSubClient;
use Monolog\Logger;


class Client
{
    protected PubSubClient $pubSubClient;
    protected string $topicName;
    protected string $subscriptionName;
    protected string $projectId;
    protected array $config;
    protected Logger $logger;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->logger = \Boot::getLogger();
        
        $topicName = $this->config['pubsub']['default_topic'];
        $subscriptionName = $this->config['pubsub']['topics'][$topicName]['subscription'] ? 
            $this->config['pubsub']['topics'][$topicName]['subscription'] : 
                $this->config['pubsub']['default_subscription'];

        $this->projectId = $this->config['google_cloud']['project_id'];
        $this->topicName = $topicName;
        $this->subscriptionName = $subscriptionName;

        // Initialize Pub/Sub client with credentials
        putenv("GOOGLE_CLOUD_PROJECT=" . $this->projectId);
        $credentialsPath = $this->config['google_cloud']['credentials_path'];
        if (file_exists($credentialsPath)) {
            $encryptKey = $this->config['google_cloud']['encrypt_key'] ?? null;
            if ($encryptKey) {
                $encryptedContent = file_get_contents($credentialsPath);
                $decryptedContent = $this->decryptCredentials($encryptedContent, $encryptKey);
                
                if ($decryptedContent === false) {
                    throw new Exception("Failed to decrypt credentials file at: {$credentialsPath}");
                }
                
                // Write decrypted content to a temporary file
                $tempCredentialsPath = sys_get_temp_dir() . '/google_credentials_' . uniqid() . '.json';
                file_put_contents($tempCredentialsPath, $decryptedContent);
                
                // Set environment variable to use the temporary decrypted file
                putenv("GOOGLE_APPLICATION_CREDENTIALS=" . $tempCredentialsPath);
                
                // Register cleanup function to delete temp file on shutdown
                register_shutdown_function(function() use ($tempCredentialsPath) {
                    if (file_exists($tempCredentialsPath)) {
                        @unlink($tempCredentialsPath);
                    }
                });
            } else {
                putenv("GOOGLE_APPLICATION_CREDENTIALS=" . $credentialsPath);
            }
        }else {
            throw new Exception("Credentials file not found at: {$credentialsPath}");
        }

        if (!empty($this->config['google_cloud']['debug'])) {
            putenv("GOOGLE_SDK_PHP_LOGGING=true");
        }
        
        $this->pubSubClient = new PubSubClient();

        $class = basename(str_replace('\\', '/', get_class($this)));
        $this->logger->info($class . " initialized for topic: {$topicName}, subscription: {$subscriptionName}:");
    }

    /**
     * Decrypt credentials file content using AES-256-CBC
     *
     * @param string $encryptedContent The encrypted content
     * @param string $encryptKey The encryption key
     * @return string|false The decrypted content or false on failure
     */
    protected function decryptCredentials(string $encryptedContent, string $encryptKey)
    {
        try {
            // Decode base64 encrypted content
            $encryptedData = base64_decode($encryptedContent);
            
            if ($encryptedData === false) {
                $this->logger->error("Failed to decode base64 encrypted content");
                return false;
            }
            
            // Extract IV (first 16 bytes for AES-256-CBC)
            $ivLength = openssl_cipher_iv_length('AES-256-CBC');
            if (strlen($encryptedData) < $ivLength) {
                $this->logger->error("Encrypted data is too short to contain IV");
                return false;
            }
            
            $iv = substr($encryptedData, 0, $ivLength);
            $ciphertext = substr($encryptedData, $ivLength);
            
            // Derive a 32-byte key from the provided key
            $key = hash('sha256', $encryptKey, true);
            
            // Decrypt the content
            $decrypted = openssl_decrypt(
                $ciphertext,
                'AES-256-CBC',
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );
            
            if ($decrypted === false) {
                $this->logger->error("Failed to decrypt credentials: " . openssl_error_string());
                return false;
            }
            
            // Validate that the decrypted content is valid JSON
            json_decode($decrypted);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error("Decrypted content is not valid JSON: " . json_last_error_msg());
                return false;
            }
            
            return $decrypted;
            
        } catch (\Exception $e) {
            $this->logger->error("Exception during decryption: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ensure topic exists, used by Producer
     *
     * @return bool
     */
    protected function ensureTopicExists()
    {
        try {
            $topic = $this->pubSubClient->topic($this->topicName);

            if (!$topic->exists()) {
                $this->logger->error("Topic '{$this->topicName}' does not exists.");
                return false;
            }else {
                $this->logger->info("Successfully connected to topic '{$this->topicName}'.");
                return true;
            }

        } catch (\Exception $e) {
            $this->logger->error("Error ensuring topic exists: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get topic info, used by Producer
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
                    'publishConfig' => $topic->info()['publishConfig'] ? $topic->info()['publishConfig'] : null
                ];
                
                $this->logger->info("Topic info retrieved: " . json_encode($info));
                return $info;
            } else {
                $this->logger->warning("Topic '{$this->topicName}' does not exist");
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
     * Create a subscription if it does not exist
     *
     * @return bool
     */
    public function ensureSubscriptionExists()
    {
        // $enableMessageOrdering = !empty($this->config['pubsub']['topics'][$this->topicName]['message_ordering']);
        try {
            $subscription = $this->pubSubClient->subscription($this->subscriptionName);

            if (!$subscription->exists()) {
                $this->logger->error("Subscription '{$this->subscriptionName}' does not exists.");
                return false;
            } else {
                $this->logger->info("Successfully listening to Subscription '{$this->subscriptionName}'.");
                return true;
            }
        } catch (\Exception $e) {
            $this->logger->error("Error ensuring subscription exists: " . $e->getMessage());
            return false;
        }
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
                    'messageRetentionDuration' => $subscriptionInfo['messageRetentionDuration'] ? $subscriptionInfo['messageRetentionDuration'] : null
                ];
                
                $this->logger->info("Subscription info retrieved: " . json_encode($info));
                return $info;
            } else {
                $this->logger->warning("Subscription '{$this->subscriptionName}' does not exist");
                return [
                    'name' => $subscription->name(),
                    'exists' => false
                ];
            }

        } catch (\Exception $e) {
            // $this->logger->debug("Getting subscription info: " . $e->getMessage());
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
}