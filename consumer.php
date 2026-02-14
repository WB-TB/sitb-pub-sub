<?php
if (php_sapi_name() !== 'cli') exit('Hanya dapat dijalankan via CLI');

require __DIR__ . '/lib/Boot.php';

Boot::init(\PubSub\Consumer::class);

// Create consumer instance
$consumer = new \PubSub\Consumer(Boot::getConfig());
$receiver = new \CKG\Receiver(Boot::getDatabase(), Boot::getSQLite(), Boot::getConfig());
$info = $consumer->getSubscriptionInfo(); // Get subscription info

echo "\n--- Starting continuous listener ---\n";
$consumer->listen([$receiver, 'prepare'], [$receiver, 'listen']);