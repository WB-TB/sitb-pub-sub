<?php

namespace Database;

use PDO;
use PDOException;
use Exception;
use Monolog\Logger;

class SQLite {
    private Logger $logger;
    private ?PDO $connection;
    private array $config;
    private string $databasePath;
    
    public function __construct(array $config) {
        $this->config = $config;
        $this->logger = \Boot::getLogger();
        $this->databasePath = $config['pubsub']['database'] ?? __DIR__ . '/pubsub.sqlite';
        $this->connect();
        $this->initializeTables();
    }
    
    private function connect() {
        try {
            // Create directory if it doesn't exist
            $directory = dirname($this->databasePath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            
            $dsn = "sqlite:{$this->databasePath}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
            ];
            
            $this->logger->debug("Connecting to SQLite database: " . $this->databasePath);
            $this->connection = new PDO($dsn, null, null, $options);
            $this->logger->debug("Successfully connected to SQLite database");
            
            // Enable WAL mode for better concurrency (important for multi-process access)
            $this->connection->exec("PRAGMA journal_mode = WAL");
            $this->logger->debug("WAL mode enabled for SQLite database");
            
            // Enable foreign keys
            $this->connection->exec("PRAGMA foreign_keys = ON");
            // Set busy timeout to 5 seconds
            $this->connection->exec("PRAGMA busy_timeout = 5000");
        } catch (PDOException $e) {
            throw new Exception("SQLite connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Initialize tables based on the schema from sql/pubsub_temp.sql
     */
    private function initializeTables() {
        $outgoingTable = $this->config['ckg']['table_outgoing'] ? $this->config['ckg']['table_outgoing'] : 'ckg_pubsub_outgoing';
        $incomingTable = $this->config['ckg']['table_incoming'] ? $this->config['ckg']['table_incoming'] : 'ckg_pubsub_incoming';

        try {
            // Create $incomingTable table
            $createIncomingTable = "
                CREATE TABLE IF NOT EXISTS {$incomingTable} (
                    id TEXT NOT NULL PRIMARY KEY,
                    data TEXT NOT NULL,
                    attributes TEXT,
                    received_at TEXT NOT NULL DEFAULT (datetime('now')),
                    processed_at TEXT NOT NULL DEFAULT (datetime('now'))
                )
            ";
            $this->connection->exec($createIncomingTable);
            $this->logger->debug("Table {$incomingTable} checked/created");
            
            // Create indexes for incoming table
            $this->connection->exec("CREATE INDEX IF NOT EXISTS idx_incoming_received_at ON {$incomingTable} (received_at)");
            $this->connection->exec("CREATE INDEX IF NOT EXISTS idx_incoming_processed_at ON {$incomingTable} (processed_at)");
            $this->logger->debug("Indexes for {$incomingTable} checked/created");
            
            // Create $outgoingTable table
            $createOutgoingTable = "
                CREATE TABLE IF NOT EXISTS {$outgoingTable} (
                    terduga_id TEXT NOT NULL PRIMARY KEY,
                    created_at TEXT NOT NULL DEFAULT (datetime('now')),
                    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
                )
            ";
            $this->connection->exec($createOutgoingTable);
            $this->logger->debug("Table {$outgoingTable} checked/created");
            
            // Create indexes for outgoing table
            $this->connection->exec("CREATE INDEX IF NOT EXISTS idx_outgoing_created_at ON {$outgoingTable} (created_at)");
            $this->connection->exec("CREATE INDEX IF NOT EXISTS idx_outgoing_updated_at ON {$outgoingTable} (updated_at)");
            $this->logger->debug("Indexes for {$outgoingTable} checked/created");
            
            $this->logger->debug("SQLite tables initialized successfully");
        } catch (PDOException $e) {
            $this->logger->error("Failed to initialize SQLite tables: " . $e->getMessage());
            throw new Exception("Failed to initialize SQLite tables: " . $e->getMessage());
        }
    }
    
    /**
     * Get PDO connection
     * @return \PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Prepare a SQL statement
     * @param string $sql
     * @return \PDOStatement
     */
    public function prepare($sql) {
        try {
            return $this->connection->prepare($sql);
        } catch (PDOException $e) {
            throw new Exception("Prepare statement failed: " . $e->getMessage());
        }
    }
    
    /**
     * Execute a query with parameters
     * @param string $sql
     * @param array $params
     * @return \PDOStatement
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("Query failed: " . $e->getMessage());
        }
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $values = array_values($data);
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        $this->query($sql, $values);
        return $this->lastInsertId();
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        $setParts = [];
        $values = [];
        
        foreach ($data as $column => $value) {
            $setParts[] = "{$column} = ?";
            $values[] = $value;
        }
        
        $setClause = implode(', ', $setParts);
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";

        $stmt = $this->query($sql, array_merge($values, $whereParams));
        return $stmt->rowCount();
    }
    
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollback() {
        return $this->connection->rollback();
    }
    
    public function __destruct() {
        $this->connection = null;
    }
    
    public function lastInsertId() {
        try {
            return $this->connection->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("Failed to get last insert ID: " . $e->getMessage());
        }
    }
}
