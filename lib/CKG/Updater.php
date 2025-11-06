<?php

namespace CKG;

use Database\MySQL;
use PubSub\Producer;
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

    public function __construct(MySQL $db, array $config) {
        $this->db = $db;
        $this->config = $config;
        $this->logger = \Boot::getLogger();
        $this->outgoingTable = $this->config['ckg']['table_outgoing'] ?? 'ckg_pubsub_outgoing';

        // Create producer instance
        $this->producer = new Producer($this->config);

        // Show topic info
        $info = $this->producer->getTopicInfo();
        echo "Topic Info:\n";
        print_r($info);
    }

    public function runPubSub($start, $end) {
        try {
            $this->logger->info("Update data dari PubSub producer mulai {$start} sampai {$end}");

            $data = $this->fetchFromDatabase($start, $end);

            $messages = $this->buildMessages($data);

            // Publikasikan pesan menggunakan producer
            $this->producer->publishBatch($messages, [
                'source' => 'sitb-ckg',
                'priority' => 'high',
                'timestamp' => (string) time(),
                'environment' => $this->config['environment']
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Error in PubSub producer: " . $e->getMessage());
        }
    }

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
    private function fetchFromDatabase($start, $end): array {
        if (empty($start) || $start == 'last') {
            $start = $this->getLastOutgoing();
        }

        if (empty($end) || $end == 'now') {
            $end = date('Y-m-d H:i:s');
        }

        $status = [];

        //TODO: Implementasi pengambilan data dari database berdasarkan rentang waktu $start dan $end

        $status[] = StatusPasien::fromArray([
            'terduga_id' => '123',
            'pasien_nik' => '3403011703850005',
            'pasien_tb_id' => '123-ab',
            'status_diagnosa' => 'TBCSO',
            'diagnosa_lab_metode' => 'TCM',
            'diagnosa_lab_hasil' => 'rif-sen',
            'tanggal_mulai_pengobatan' => null,
            'tanggal_selesai_pengobatan' => null,
            'hasil_akhir' => null
        ]);

        return $status;
    }

    private function getLastOutgoing() {
        try {
            $outgoingTable = $this->outgoingTable;
            $sql = "SELECT MAX(created_at) as last_timestamp FROM {$outgoingTable}";
            $result = $this->db->fetchOne($sql);
            
            return $result['last_timestamp'] ?? null;
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
        foreach ($data as $statusPasien) {
            $messages[] = $statusPasien->__toString();
        }

        return $messages;
    }

    /**
     * Send status pasien data via API Client
     * @param StatusPasien[] $statusPasien
     */
    private function sendViaApiClient(array $statusPasien) {
        try {
            $apiConfig = $this->config['api'] ?? [];
            $apiClient = new ApiClient($apiConfig);
            $batchSize = $apiConfig['batch_size'] && $apiConfig['batch_size'] > 1 ? $apiConfig['batch_size'] : 100;
            $endpoint = '/v1/ckg/tb/status-pasien';
            $batchSize = min(count($statusPasien), $batchSize, 500); // Batasi maksimal batch size ke 500
            
            // Process data in batches if batchSize is configured
            $this->sendBatchedData($apiClient, $statusPasien, $batchSize, $endpoint);
        } catch (\Exception $e) {
            $this->logger->error("Error sending status pasien via API Client: " . $e->getMessage());
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
            $this->logger->error("Failed to send status pasien via API Client. Status Code: " . $response['status_code'] . " Response: " . ($response['body'] ?? 'No response body'));
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

}