<?php

namespace CKG;

use Database\MySQL;
use Database\LapTbc03;
use PubSub\Producer;
use CKG\Format\PubSubObjectWrapper;
use CKG\Format\StatusPasien;
use Monolog\Logger;
use Api\Client as ApiClient;

class Updater
{
    private MySQL $db;
    private array $config;
    private Logger $logger;
    private Producer $producer;
    private $outgoingTable;
    private $incomingTable;
    private LapTbc03 $laporan;

    public function __construct(MySQL $db, array $config) {
        $this->db = $db;
        $this->config = $config;
        $this->logger = \Boot::getLogger();
        $this->outgoingTable = $this->config['ckg']['table_outgoing'] ? $this->config['ckg']['table_outgoing'] : 'ckg_pubsub_outgoing';
        $this->incomingTable = $this->config['ckg']['table_incoming'] ? $this->config['ckg']['table_incoming'] : 'ckg_pubsub_incoming';
        $this->laporan = new LapTbc03($db, $config);

        // Create producer instance
        $this->producer = new Producer($this->config);

        if (isset($this->config['producer_mode']) && $this->config['producer_mode'] === 'pubsub') {
            $this->logger->info("Google Pub/Sub mode selected for Updater");
            // Show topic info
            $info = $this->producer->getTopicInfo();
            echo "Topic Info:\n";
            print_r($info);
        } else {
            $this->logger->info("API Client mode selected for Updater");
        }
    }

    public function runPubSub($start, $end) {
        try {
            $this->logger->info("Update data dari PubSub producer mulai {$start} sampai {$end}");

            $data = $this->fetchFromDatabase($start, $end);

            $messages = $this->buildMessages($data);

            // Publikasikan pesan menggunakan producer
            if (count($messages) > 0) {
                $this->producer->publishBatch($messages, [
                    'source' => 'sitb-ckg',
                    'priority' => 'high',
                    'timestamp' => (string) time(),
                    'environment' => $this->config['environment']
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error("Error in PubSub producer: " . $e->getMessage());
        }
    }

    /**
     * 
     * @param string $start
     * @param string $end
     */
    public function runApiClient($start, $end) {
        try {
            $this->logger->info("Update data dari API Client mulai {$start} sampai {$end}");
            $data = $this->fetchFromDatabase($start, $end);
            $this->sendViaApiClient($data);

            $this->saveOutgoingRecord($data);
        } catch (\Exception $e) {
            $this->logger->error("Error in API Client call: " . $e->getMessage());
        }
    }

    /**
     * Fetch data from database to be sent as messages
     * 
     * @return StatusPasien[]
     */
    private function fetchFromDatabase($start, $end, $mode = 'api', $limit = 0): array {
        if ($mode == 'pubsub')
            $this->removePubSubMessages();

        if (empty($start) || $start == 'last') {
            $start = $this->getLastOutgoing();
            if (empty($start))
                $start = date('Y-m-d H:i:s', strtotime('-1 day'));
        }

        if (empty($end) || $end == 'now') {
            $end = date('Y-m-d H:i:s');
        }

        if ($limit < 1) {
            $limit = $this->config['producer']['batch_size'];
        }
        
        $status = [];

        // Proses SO
        $statusSo = $this->laporan->getData(LapTbc03::TYPE_SO, $start, $end, false, $limit);
        foreach ($statusSo as $item) {
            $item['diagnosis'] = 'TBC SO';
            $statusPasien = new StatusPasien();
            $statusPasien->fromDbRecord($item);
            $status[] = $statusPasien;
        }
        $this->logger->debug("Sending TB SO " . count($status) . " items");

        // Proses RO
        $statusRo = $this->laporan->getData(LapTbc03::TYPE_RO, $start, $end, false, $limit);
        foreach ($statusRo as $item) {
            $item['diagnosis'] = 'TBC RO';
            $statusPasien = new StatusPasien();
            $statusPasien->fromDbRecord($item);
            $status[] = $statusPasien;
        }
        $this->logger->debug("Sending TB RO " . count($status) . " items");

        // $statusPasien = new StatusPasien();
        // $status[] = $statusPasien->fromArray([
        //     'terduga_id' => '123',
        //     'pasien_nik' => '3403011703850005',
        //     'pasien_tb_id' => '123-ab',
        //     'status_diagnosa' => 'TBCSO',
        //     'diagnosa_lab_metode' => 'TCM',
        //     'diagnosa_lab_hasil' => 'rif-sen',
        //     'tanggal_mulai_pengobatan' => null,
        //     'tanggal_selesai_pengobatan' => null,
        //     'hasil_akhir' => null
        // ]);

        return $status;
    }

    private function getLastOutgoing() {
        try {
            $outgoingTable = $this->outgoingTable;
            $sql = "SELECT MAX(created_at) as last_timestamp FROM {$outgoingTable}";
            $result = $this->db->fetchOne($sql);
            
            return $result['last_timestamp'] ? $result['last_timestamp'] : null;
        } catch (\Exception $e) {
            $this->logger->error("Failed to get last outgoing timestamp: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Build messages from data to be sent via PubSub Producer
     * 
     * @param StatusPasien[] $data
     * @return string[]
     */
    private function buildMessages(array $data) {
        // Implementasi pembuatan message dari data untuk dikirim via PubSub Producer
        $messages = [];
        if (count($data) > 0) {
            $status = PubSubObjectWrapper::NewProduce(StatusPasien::class, $data);
            $messages[] = $status->toJson();
        }

        return $messages;
    }

    /**
     * Send status pasien data via API Client
     * @param StatusPasien[] $statusPasien
     */
    private function sendViaApiClient(array $statusPasien) {
        if (count($statusPasien) > 0) {
            try {
                    $apiConfig = $this->config['api'] ? $this->config['api'] : [];
                    $apiClient = new ApiClient($apiConfig);
                    $batchSize = $apiConfig['batch_size'] && $apiConfig['batch_size'] > 1 ? $apiConfig['batch_size'] : 100;
                    $endpoint = '/tb/status-pasien';
                    $batchSize = min(count($statusPasien), $batchSize, 500); // Batasi maksimal batch size ke 500
                    
                    // Process data in batches if batchSize is configured
                    $this->sendBatchedData($apiClient, $statusPasien, $batchSize, $endpoint);
            } catch (\Exception $e) {
                $this->logger->error("Error sending status pasien via API Client: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Send data in batches to avoid payload size issues
     *
     * @param ApiClient $apiClient
     * @param array $statusPasien
     * @param int $batchSize
     * @param string $endpoint
     */
    private function sendBatchedData($apiClient, array $statusPasien, int $batchSize, string $endpoint) {
        $totalItems = count($statusPasien);
        $batches = array_chunk($statusPasien, $batchSize);
        
        $this->logger->info("Processing {$totalItems} items in " . count($batches) . " batches of max {$batchSize} items each");
        
        foreach ($batches as $index => $batch) {
            $payload = array_map(function($item) {
                return $item->toArray();
            }, $batch);
            
            $this->logger->info("Sending batch " . ($index + 1) . " with " . count($batch) . " items");
            $this->sendApiRequest($apiClient, $endpoint, $payload);
        }
    }
    
    /**
     * Send a single API request
     *
     * @param ApiClient $apiClient
     * @param string $endpoint
     * @param array $payload
     */
    private function sendApiRequest($apiClient, string $endpoint, array $payload) {
        $response = $apiClient->post($endpoint, $payload, [
            'Content-Type: application/json'
        ]);

        if ($response['status_code'] == 200 || $response['status_code'] == 201) {
            $this->logger->info("Successfully sent status pasien via API Client. Items count: " . count($payload));
        } else {
            $this->logger->error("Failed to send status pasien via API Client. Status Code: " . $response['status_code'] . " Response: " . ($response['body'] ? $response['body'] : 'No response body'));
        }
    }

    /**
     * Save outgoing record to database
     *
     * @param StatusPasien[] $data
     */
    private function saveOutgoingRecord(array $data) {
        $ids = array_map(fn($item) => $item->terduga_id, $data);
        $existing = $this->getExistingOutgoing($ids);
        $outgoingTable = $this->outgoingTable;
        $sqlInsert = "INSERT INTO {$outgoingTable} (terduga_id, updated_at) VALUES (?, NOW())";
        $sqlUpdate = "UPDATE {$outgoingTable} SET updated_at = NOW() WHERE terduga_id = ?";

        foreach ($data as $statusPasien) {
            try {
                $params = [
                    $statusPasien->terduga_id,
                ];
                if (in_array($statusPasien->terduga_id, $existing)) {
                    // Update existing record
                    $this->db->query($sqlUpdate, $params);
                } else {
                    // Insert new record
                    $this->db->query($sqlInsert, $params);
                }
            } catch (\Exception $e) {
                $this->logger->error("Failed to save outgoing record: " . $e->getMessage());
            }
        }
    }

    private function getExistingOutgoing(array $ids): array {
        if (empty($ids)) {
            return [];
        }

        $outgoingTable = $this->outgoingTable;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $query = "SELECT terduga_id FROM {$outgoingTable} WHERE terduga_id IN ({$placeholders})";
        $stmt = $this->db->prepare($query);
        $stmt->execute($ids);
        $result = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        return $result ?: [];
    }

    private function removePubSubMessages() {
        $incomingTable = $this->incomingTable;
        $sqlIncoming = "DELETE FROM {$incomingTable} WHERE processed_at IS NOT NULL AND created_at < NOW() - INTERVAL 1 DAY";

        $outgoingTable = $this->outgoingTable;
        $sqlOutgoing = "DELETE FROM {$outgoingTable} WHERE created_at < NOW() - INTERVAL 1 DAY";
        try {
            $deleted1 = $this->db->query($sqlIncoming);
            $deleted2 = $this->db->query($sqlOutgoing);
            $this->logger->info("Removed old PubSub messages from incoming and outgoing table.");
        } catch (\Exception $e) {
            $this->logger->error("Failed to remove old PubSub messages: " . $e->getMessage());
        }
    }
}