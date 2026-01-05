<?php
if (php_sapi_name() !== 'cli') exit('Hanya dapat dijalankan via CLI');

require __DIR__ . '/lib/Boot.php';

Boot::init(\PubSub\Consumer::class);

// Create consumer instance
$consumer = new \PubSub\Consumer(Boot::getConfig());
$receiver = new \CKG\Receiver(Boot::getDatabase(), Boot::getConfig());

// Show subscription info
$info = $consumer->getSubscriptionInfo();
echo "Subscription Info:\n";
print_r($info);

// Start continuous listener
echo "\n--- Starting continuous listener (press Ctrl+C to stop) ---\n";
$consumer->listen([$receiver, 'prepare'], [$receiver, 'listen']);