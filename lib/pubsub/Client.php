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
        $subscriptionName = $this->config['pubsub']['topics'][$topicName]['subscription'] ?? $this->config['pubsub']['default_subscription'];

        $this->projectId = $this->config['google_cloud']['project_id'];
        $this->topicName = $topicName;
        $this->subscriptionName = $subscriptionName;

        // Initialize Pub/Sub client with credentials
        putenv("GOOGLE_CLOUD_PROJECT=" . $this->projectId);
        $credentialsPath = $this->config['google_cloud']['credentials_path'];
        if (file_exists($credentialsPath)) {
            // $jsonKey = json_decode(file_get_contents($credentialsPath), true);
            // $clientConfig['keyFile'] = $jsonKey;
            putenv("GOOGLE_APPLICATION_CREDENTIALS=" . $credentialsPath);
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
                    'publishConfig' => $topic->info()['publishConfig'] ?? null
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
                    'messageRetentionDuration' => $subscriptionInfo['messageRetentionDuration'] ?? null
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
            $this->logger->error("Error getting subscription info: " . $e->getMessage());
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