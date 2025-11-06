<?php

namespace CKG;

use Database\MySQL;
use CKG\Format\SkriningCKG;
use Monolog\Logger;

class Receiver
{
    private MySQL $db;
    private $config;
    private Logger $logger;
    private $incomingTable;
    private $processedTable;
    private $skriningTable;
    
    public function __construct(MySQL $db, array $config) {
        $this->db = $db;
        $this->config = $config;
        $this->logger = \Boot::getLogger();
        $this->incomingTable = $this->config['ckg']['table_incoming'] ?? 'ckg_pubsub_incoming';
        $this->processedTable = $this->config['ckg']['table_processed'] ?? 'ckg_pubsub_processed';
        $this->skriningTable = $this->config['ckg']['table_skrining'] ?? 'skrining_tb';
    }

    public function prepare(array $rawMessages): array {;
        $validIds = $this->getExistingMessageIds(array_keys($rawMessages));
        $updates = $this->getExistingData($rawMessages, $validIds);
        $valid = [];
        foreach ($rawMessages as $messageId => $message) {
            if (in_array($messageId, $validIds)) {
                $this->logger->debug("Skipping existing message {$messageId}.");
                continue;
            }

            try {
                $data = $message->data();
                $skrining = SkriningCKG::fromJson($data);
                
                // Hanya proses objek CKG yang valid
                if ($skrining->isCkgObject()) {
                    // sertakan info $skrining->pasien_ckg_id di database, apakah sudah ada atau belum
                    // jika belum ada (nil), maka ini pesan baru
                    // berisi array dengan kombinasi index-0 $skrining, index-1 $message, index-2 record db skrining.id (null jika baru)
                    $valid[$messageId] = [$skrining, $message, isset($updates[$skrining->pasien_ckg_id]) ? $updates[$skrining->pasien_ckg_id] : null];
                }

                $this->logNewMessage($messageId, $data, $message->attributes());
            } catch (\Exception $e) {
                $this->logger->warning("Error preparing message {$messageId}: " . $e->getMessage());
            }
        }

        return $valid;
    }

    public function listen($skrining, $message, $skriningId = null): bool{
        try {
            // Hanya proses objek CKG yang valid
            if ($skrining->isCkgObject()) {
                $messageId = $message->id();
                $data = $message->data();
                $attributes = $message->attributes();
                $ackId = $message->ackId();
                $this->logger->info("Received valid CKG SkriningCKG object {$messageId} - {$ackId}. {$skrining}");
                
                echo "Received message at " . date('Y-m-d H:i:s') . ":\n";
                echo "  Data: {$data}\n";
                echo "  Attributes: " . json_encode($attributes) . "\n";

                $this->saveToDatabase($skrining, $skriningId);

                // Return true to acknowledge, false to leave for retry
                return true;
            }

            // Simulate message processing
            // sleep(1);
        } catch (\Exception $e) {
            $this->logger->warning("Error processing message: " . $e->getMessage());
        }

        return false;
    }

    private function getExistingMessageIds(array $ids): array {
        if (empty($ids)) {
            return [];
        }

        $incomingTable = $this->incomingTable;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $query = "SELECT id FROM {$incomingTable} WHERE id IN ({$placeholders})";
        $stmt = $this->db->prepare($query);
        $stmt->execute($ids);
        $result = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        return $result ?: [];
    }

    private function getExistingData(array $rawMessages, array $validIds): array {
        $exists = [];
        foreach ($rawMessages as $messageId => $message) {
            // messageId tidak ada di validIds, lewati
            if (!in_array($messageId, $validIds)) {
                continue;
            }

            try {
                $data = $message->data();
                $skrining = SkriningCKG::fromJson($data);
                if ($skrining->isCkgObject() && isset($skrining->pasien_ckg_id)) {
                    $exists[] = $skrining->pasien_ckg_id;
                }
            } catch (\Exception $e) {
                $this->logger->warning("Error preparing message {$messageId}: " . $e->getMessage());
            }
        }

        if (empty($exists)) {
            return [];
        }

        $processedTable = $this->processedTable;
        $placeholders = implode(',', array_fill(0, count($exists), '?'));
        $query = "SELECT ckg_id, id FROM {$processedTable} WHERE ckg_id IN ({$placeholders})";
        $stmt = $this->db->prepare($query);
        $stmt->execute($exists);
        $result = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);

        return $result ?: [];
    }

    private function logNewMessage($messageId, $data, $attributes) {
        $this->logger->debug("Logging message {$messageId} to database.");
        $incomingTable = $this->incomingTable;
        $query = "INSERT INTO {$incomingTable} (id, data, attributes, received_at) VALUES (?, ?, ?, NOW())";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $messageId,
            json_encode($data),
            json_encode($attributes)
        ]);
    }

    private function saveToDatabase(SkriningCKG $skrining, $skriningId = null) {
        $this->logger->info("Saving SkriningCKG to database: {$skrining}");
        $data = $skrining->toArray();
        $skriningTable = $this->skriningTable;

        // Jika skriningId tidak null, berarti ini update
        if ($skriningId) {
            $placeholders = implode(', ', array_map(fn($key) => "{$key} = ?", array_keys($data)));
            $query = "UPDATE {$skriningTable} SET {$placeholders}, updated_at = NOW() WHERE id = ?";
            $params = array_merge(array_values($data), $skriningId);

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            $this->logger->debug("Update Skrining with ID: {$skriningId}");
        } else {
            $keyPlaceholders = implode(', ', array_keys($data));
            $valuePlaceholders = implode(', ', array_fill(0, count($data), '?'));
            $query = "INSERT INTO {$skriningTable} (ckg_id, {$keyPlaceholders}, created_at) VALUES (?, {$valuePlaceholders}, NOW())";
            $params = array_merge($skrining->pasien_ckg_id, array_values($data));

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $skriningId = $this->db->lastInsertId();

            $query2 = "INSERT INTO {$this->processedTable} (id, ckg_id, processed_at) VALUES (?, ?, NOW())";
            $stmt2 = $this->db->prepare($query2);
            $stmt2->execute([$skriningId, $skrining->pasien_ckg_id]);

            $this->logger->debug("New Skrining saved with ID: {$skriningId}");
        }
    }
}