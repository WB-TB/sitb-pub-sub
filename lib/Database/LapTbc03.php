<?php

namespace Database;

/**
 * Class LapTbc03
 * 
 * Handles database operations for ta_skrining table
 */
class LapTbc03
{
    public const TYPE_SO = 1;
    public const TYPE_RO = 2;

    private MySQL $db;
    private $tableNameSo;
    private $tableNameRo;
    
    /**
     * Constructor
     * 
     * @param MySQL $db
     * @param array $config
     */
    public function __construct(MySQL $db, array $config)
    {
        $this->db = $db;
        $this->tableNameSo = $config['ckg']['table_laporan_so'] ? $config['ckg']['table_laporan_so'] : 'lap_tbc_03so';
        $this->tableNameRo = $config['ckg']['table_laporan_ro'] ? $config['ckg']['table_laporan_ro'] : 'lap_tbc_03ro';
    }

    /**
     * Find records by datetime rage of updated_at
     * 
     * @param int $type tipe table SO atau RO
     * @param string $start datetime mulai
     * @param string $end datetime sampai
     * @return array|int
     */
    public function getData(int $type, string $start, string $end, bool $count = false, int $limit = 1000): array|int
    {
        $tableName = $type == self::TYPE_RO ? $this->tableNameRo : $this->tableNameSo;
        if ($count) {
            $sql = "SELECT COUNT(*) jumlah FROM {$tableName} WHERE update_at > ? and update_at <= ? LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$start, $end, $limit]);
            $result = $stmt->fetch(\PDO::FETCH_COLUMN);

            return intval($result);
        } else {
            $sql = "SELECT * FROM {$tableName} WHERE update_at > ? and update_at <= ? LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$start, $end, $limit]);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            return $result ?: [];
        }
    }
}