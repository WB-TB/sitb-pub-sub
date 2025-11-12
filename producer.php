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

// // Publish a single message
// echo "\nPublishing single message...\n";
// $messageId = $producer->publish('[{"terduga_id":"123","pasien_nik":"3403011703850005","pasien_tb_id":"123-ab","status_diagnosa":"TBCSO","diagnosa_lab_metode":"TCM","diagnosa_lab_hasil":"rif-sen","tanggal_mulai_pengobatan":null,"tanggal_selesai_pengobatan":null,"hasil_akhir":null}]', [
//     'source' => 'sitb-ckg',
//     'timestamp' => (string) time(),
//     'environment' => Boot::getConfig()['environment']
// ]);

// if ($messageId) {
//     echo "Message published with ID: {$messageId}\n";
// } else {
//     echo "Failed to publish message\n";
// }

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
];s

$messageIds = $producer->publishBatch($messages, $attributes);

if (!empty($messageIds)) {
    echo "\nSuccessfully published " . count($messageIds) . " messages!\n";
} else {
    echo "\nFailed to publish batch messages\n";
}*/