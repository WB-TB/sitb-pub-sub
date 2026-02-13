<?php

namespace Database;
use PDO;
use PDOException;
use Exception;
use Monolog\Logger;

class MySQL {
    private Logger $logger;
    private ?PDO $connection;
    private array $config;
    
    public function __construct(array $config) {
        $this->config = $config;
        $this->logger = \Boot::getLogger();
        $this->connect();
    }
    
    private function connect() {
        try {
            $dbConfig = $this->config['database'];
            
            $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database_name']}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
                // PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                PDO::ATTR_TIMEOUT => 30,
            ];
            
            $this->logger->info("Connecting to database: " . $dsn);
            $this->connection = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
            $this->logger->info("Success connect to Database");            
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Check if connection is alive and reconnect if needed
     */
    private function ensureConnection() {
        try {
            // Try to execute a simple query to check connection
            $this->logger->debug("Checking connection: SELECT 1;");
            $this->connection->query("SELECT 1");
        } catch (PDOException $e) {
            // Connection is lost, reconnect
            $this->connect();
        }
    }
    
    /**
     * Check if the exception is a "server has gone away" error
     */
    private function isServerGoneAway(PDOException $e): bool {
        $this->logger->error("Database error: Server gone away");
        return $e->errorInfo[1] === 2006;
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
        $retryCount = 0;
        $maxRetries = 3;
        
        while ($retryCount < $maxRetries) {
            try {
                $this->ensureConnection();
                return $this->connection->prepare($sql);
            } catch (PDOException $e) {
                if ($this->isServerGoneAway($e) && $retryCount < $maxRetries - 1) {
                    $retryCount++;
                    $this->connect();
                    continue;
                }
                $this->logger->debug("Prepare statement failed: " . $e->getMessage());
                throw new Exception("Prepare statement failed: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Execute a query with parameters
     * @param string $sql
     * @param array $params
     * @return \PDOStatement
     */
    public function query($sql, $params = []) {
        $retryCount = 0;
        $maxRetries = 3;
        
        while ($retryCount < $maxRetries) {
            try {
                $this->ensureConnection();
                $stmt = $this->connection->prepare($sql);
                $this->logger->debug("Executing prepare sql statement: " . $sql);
                $stmt->execute($params);
                $this->logger->debug("Executing select query: " . $sql);
                return $stmt;
            } catch (PDOException $e) {
                if ($this->isServerGoneAway($e) && $retryCount < $maxRetries - 1) {
                    $retryCount++;
                    $this->connect();
                    continue;
                }
                throw new Exception("Query failed: " . $e->getMessage());
            }
        }
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        $this->logger->debug("Fetch all");
        return $stmt->fetchAll();
    }
    
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        $this->logger->debug("Fetch");
        return $stmt->fetch();
    }
    
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $values = array_values($data);
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->logger->debug("Executing insert query: " . $sql);
        
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
        
        $this->logger->debug("Executing update query: " . $sql);
        $stmt = $this->query($sql, array_merge($values, $whereParams));
        return $stmt->rowCount();
    }
    
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $this->logger->debug("Executing delete query: " . $sql);
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    public function beginTransaction() {
        $this->ensureConnection();
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        $this->ensureConnection();
        return $this->connection->commit();
    }
    
    public function rollback() {
        $this->ensureConnection();
        return $this->connection->rollback();
    }
    
    public function __destruct() {
        $this->connection = null;
    }
    
    public function lastInsertId() {
        $retryCount = 0;
        $maxRetries = 3;
        
        while ($retryCount < $maxRetries) {
            try {
                $this->ensureConnection();
                return $this->connection->lastInsertId();
            } catch (PDOException $e) {
                if ($this->isServerGoneAway($e) && $retryCount < $maxRetries - 1) {
                    $retryCount++;
                    $this->connect();
                    continue;
                }
                throw new Exception("Failed to get last insert ID: " . $e->getMessage());
            }
        }
    }
}