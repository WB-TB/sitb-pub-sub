<?php
if (php_sapi_name() !== 'cli') exit('Hanya dapat dijalankan via CLI');

require __DIR__ . '/lib/Boot.php';

Boot::init(\PubSub\Producer::class);
$cliParams = Boot::getCliParams();

if (isset($cliParams['start'])) {
    if (!Boot::validateDateFormat($cliParams['start'])) {
        exit("Error: Invalid date format for 'start' parameter. Expected format: YYYY-MM-DD or YYYY-MM-DD HH:MM:SS\n");
    }
    $start = $cliParams['start'];

} else {
    $start = date('Y-m-d H:i:s', strtotime('-1 day'));
}

if (isset($cliParams['end'])) {
    if (!Boot::validateDateFormat($cliParams['end'])) {
        exit("Error: Invalid date format for 'end' parameter. Expected format: YYYY-MM-DD or YYYY-MM-DD HH:MM:SS\n");
    }
    $end = $cliParams['end'];

} else {
    $end = 'now';
}

$updater = new \CKG\Updater(Boot::getDatabase(), Boot::getSQLite(), Boot::getConfig());
if (isset($cliParams['mode']) && $cliParams['mode'] === 'pubsub') {
    $updater->runPubSub('last', $end);
} else {
    $updater->runApiClient($start, $end);
}