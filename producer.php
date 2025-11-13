<?php
if (php_sapi_name() !== 'cli') exit('Hanya dapat dijalankan via CLI');

require __DIR__ . '/lib/Boot.php';

Boot::init(\PubSub\Producer::class);
$cliParams = Boot::getCliParams();

$updater = new \Ckg\Updater(Boot::getDatabase(), Boot::getConfig());
if (isset($cliParams['mode']) && $cliParams['mode'] === 'pubsub') {
    $end = $cliParams['end'] ?? 'now';
    $updater->runPubSub('last', $end);
}else {
    $start = $cliParams['start'] ?? date('Y-m-d H:i:s', strtotime('-1 day'));
    $end = $cliParams['end'] ?? 'now';
    $updater->runApiClient($start, $end);
}