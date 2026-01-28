<?php

namespace Database;

use CKG\Format\SkriningCKG;
use Monolog\Logger;

/**
 * Class TaSkrining
 * 
 * Handles database operations for ta_skrining table
 */
class TaSkrining
{
    private MySQL $db;
    private Logger $logger;
    private $tableName;
    
    /**
     * Constructor
     * 
     * @param MySQL $db
     * @param array $config
     */
    public function __construct(MySQL $db, array $config)
    {
        $this->db = $db;
        $this->logger = \Boot::getLogger();
        $this->tableName = $config['ckg']['table_skrining'] ? $config['ckg']['table_skrining'] : 'ta_skrining';
    }
    
    /**
     * Insert or update screening data
     * 
     * @param SkriningCKG $skrining
     * @param int|null $existingId
     * @return int|false
     */
    public function save(SkriningCKG $skrining)
    {
        try {
            $data = $skrining->toDbRecord();
            $this->logger->debug("Simpan Data Skrining TBC: " . json_encode($data, JSON_PRETTY_PRINT));
            if (!isset($data['ckg_id']))
                throw new \Exception('Data tidak memiliki Pasien ID CKG');
            
            $row = $this->findByCkgId($data['ckg_id']);
            if (!empty($row) && $row['id']) {
                $existingId = $row['id'];

                // bersihkan dulu
                unset($data['id']);
                unset($data['insert_by']);
                unset($data['insert_at']);

                // Update existing record
                $result = $this->update($existingId, $data);
                $this->logger->info("Update Skrining with ID: {$existingId}");

                $update = false;
            } else {
                // bersihkan dulu
                unset($data['id']);
                unset($data['update_by']);
                unset($data['update_at']);

                // Insert new record
                $result = $this->insert($data);
                $this->logger->info("New Skrining saved with ID: {$result}");

                $update = true;
            }
            
            return [$result, $update];
        } catch (\Exception $e) {
            $this->logger->error("Error saving Skrining data: " . $e->getMessage());
        }

        return [false, false];
    }
    
    /**
     * Insert new record
     * 
     * @param array $data
     * @return int|false
     */
    private function insert(array $data)
    {
        // Remove auto-generated fields
        unset($data['id']);
        
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($data), '?');
        
        $sql = "INSERT INTO {$this->tableName} (" . implode(', ', $columns) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Update existing record
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    private function update(int $id, array $data)
    {
        // Remove auto-generated fields
        unset($data['id']);
        
        $setClauses = [];
        foreach ($data as $key => $value) {
            $setClauses[] = "{$key} = ?";
        }
        
        $sql = "UPDATE {$this->tableName} SET " . implode(', ', $setClauses) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $params = array_values($data);
        $params[] = $id;
        
        return $stmt->execute($params);
    }
    
    /**
     * Find record by CKG ID
     * 
     * @param string $ckgId
     * @return array|null
     */
    public function findByCkgId(string $ckgId): ?array
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE ckg_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$ckgId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result ? $result : null;
    }
    
    /**
     * Find record by NIK
     * 
     * @param string $nik
     * @return array|null
     */
    public function findByNik(string $nik): ?array
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE nik = ? ORDER BY tgl_skrining DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$nik]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result ? $result : null;
    }
}