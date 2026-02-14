<?php

namespace CKG;

use Database\MySQL;
use Database\SQLite;
use Database\TaSkrining;
use CKG\Format\PubSubObjectWrapper;
use CKG\Format\SkriningCKG;
use Monolog\Logger;

class Receiver
{
    private SQLite $sqlite;
    private $config;
    private Logger $logger;
    private $incomingTable;
    private $skriningModel;
    
    public function __construct(MySQL $db, SQLite $sqlite, array $config) {
        $this->sqlite = $sqlite;
        $this->config = $config;
        $this->logger = \Boot::getLogger();
        $this->incomingTable = $this->config['ckg']['table_incoming'] ? $this->config['ckg']['table_incoming'] : 'ckg_pubsub_incoming';
        $this->skriningModel = new TaSkrining($db, $config);
    }

    public function prepare(array $rawMessages): array {;
        $validIds = $this->getExistingMessageIds(array_keys($rawMessages));
        // $updates = $this->getExistingData($rawMessages, $validIds);
        $this->logger->debug("Prepare Messages: " . json_encode($rawMessages, JSON_PRETTY_PRINT));
        $valid = [];
        foreach ($rawMessages as $messageId => $message) {
            if (in_array($messageId, $validIds)) {
                $this->logger->debug("Skipping existing message {$messageId}.");
                continue;
            }

            try {
                $data = $message->data();
                // $this->logger->debug("Raw Data: " . print_r($data, true));
                $skrining = PubSubObjectWrapper::NewConsume(SkriningCKG::class);
                $skrining->fromJson($data);
                $this->logger->debug("Parsing Skrining: " . json_encode($skrining, JSON_PRETTY_PRINT));
                
                // Hanya proses objek CKG yang valid
                if ($skrining->isCkgObject()) {
                    // sertakan info $skrining->pasien_ckg_id di database, apakah sudah ada atau belum
                    // jika belum ada (nil), maka ini pesan baru
                    // berisi array dengan kombinasi index-0 $skrining, index-1 $message, index-2 record db skrining.id (null jika baru)
                    $valid[$messageId] = [$skrining, $message];
                }

                $this->logNewMessage($messageId, $data, $message->attributes());
            } catch (\Exception $e) {
                $this->logger->warning("Error preparing message {$messageId}: " . $e->getMessage());
            }
        }

        return $valid;
    }

    public function listen($skrining, $message): bool{
        try {
            // Hanya proses objek CKG yang valid
            if ($skrining->isCkgObject()) {
                $messageId = $message->id();
                $data = $message->data();
                $attributes = $message->attributes();
                $ackId = $message->ackId();
                $this->logger->info("Received valid CKG SkriningCKG object {$messageId} - {$ackId}. {$skrining}");
                
                // echo "Received message at " . date('Y-m-d H:i:s') . ":\n";
                // echo "  Data: {$data}\n";
                // echo "  Attributes: " . json_encode($attributes) . "\n";
                $this->logger->debug("Received message at " . date('Y-m-d H:i:s') . ":\nData {$data}\n  Attributes: " . json_encode($attributes, JSON_PRETTY_PRINT));

                $items = $skrining->getData();
                foreach ($items as $item) {
                    $this->saveToDatabase($item);
                }

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
        $this->logger->debug("Check Incomming Message Ids: {$query}");
        $stmt = $this->sqlite->prepare($query);
        $stmt->execute($ids);
        $result = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        return $result ? $result : [];
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
                $skrining = PubSubObjectWrapper::NewConsume(SkriningCKG::class);
                $skrining->fromJson($data);
                // $skrining = SkriningCKG::fromJson($data);
                if ($skrining->isCkgObject() && isset($skrining->pasien_ckg_id)) {
                    // $exists[] = $skrining->pasien_ckg_id;
                }
            } catch (\Exception $e) {
                $this->logger->warning("Error preparing message {$messageId}: " . $e->getMessage());
            }
        }

        if (empty($exists)) {
            return [];
        }

        $incomingTable = $this->incomingTable;
        $placeholders = implode(',', array_fill(0, count($exists), '?'));
        $query = "SELECT ckg_id, id FROM {$incomingTable} WHERE ckg_id IN ({$placeholders})";
        $stmt = $this->sqlite->prepare($query);
        $stmt->execute($exists);
        $result = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);

        return $result ? $result : [];
    }

    private function logNewMessage($messageId, $data, $attributes) {
        $this->logger->debug("Logging message {$messageId} to database.");
        $incomingTable = $this->incomingTable;
        $query = "INSERT INTO {$incomingTable} (id, data, attributes, received_at) VALUES (?, ?, ?, datetime('now'))";
        $stmt = $this->sqlite->prepare($query);
        $stmt->execute([
            $messageId,
            json_encode($data),
            json_encode($attributes)
        ]);
    }

    private function saveToDatabase(SkriningCKG $skrining) {
        list($skriningId, $update) = $this->skriningModel->save($skrining);
        if ($skriningId) {
            $incomingTable = $this->incomingTable;
            $query = "UPDATE {$incomingTable} SET processed_at = datetime('now') WHERE id = ?";
            $stmt2 = $this->sqlite->prepare($query);
            $stmt2->execute([$skrining->pasien_ckg_id]);
        }
    }
}