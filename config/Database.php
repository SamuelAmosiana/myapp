<?php
/**
 * Database Connection Class for ClassReserve CHAU
 * File: config/Database.php
 */

class Database {
    // Database configuration
    private $host = 'localhost';
    private $db_name = 'classreserve_chau';
    private $username = 'root'; // Change this for production
    private $password = '';     // Change this for production
    private $charset = 'utf8mb4';
    
    // Connection instance
    private $pdo;
    private static $instance = null;
    
    /**
     * Private constructor for Singleton pattern
     */
    private function __construct() {
        $this->connect();
    }
    
    /**
     * Get single instance of Database (Singleton pattern)
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Create database connection
     */
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch (PDOException $e) {
            die("Database Connection Error: " . $e->getMessage());
        }
    }
    
    /**
     * Get PDO connection
     * @return PDO
     */
    public function getConnection() {
        return $this->pdo;
    }
    
    /**
     * Execute a query and return results
     * @param string $query
     * @param array $params
     * @return PDOStatement
     */
    public function query($query, $params = []) {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database Query Error: " . $e->getMessage());
            throw new Exception("Database query failed");
        }
    }
    
    /**
     * Fetch single row
     * @param string $query
     * @param array $params
     * @return array|false
     */
    public function fetchOne($query, $params = []) {
        $stmt = $this->query($query, $params);
        return $stmt->fetch();
    }
    
    /**
     * Fetch all rows
     * @param string $query
     * @param array $params
     * @return array
     */
    public function fetchAll($query, $params = []) {
        $stmt = $this->query($query, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Insert data and return last insert ID
     * @param string $table
     * @param array $data
     * @return string
     */
    public function insert($table, $data) {
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $query = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($query, $data);
        
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Update data
     * @param string $table
     * @param array $data
     * @param string $where
     * @param array $whereParams
     * @return int Number of affected rows
     */
    public function update($table, $data, $where, $whereParams = []) {
        $setClause = [];
        foreach ($data as $key => $value) {
            $setClause[] = "{$key} = :{$key}";
        }
        $setClause = implode(', ', $setClause);
        
        $query = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $params = array_merge($data, $whereParams);
        
        $stmt = $this->query($query, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Delete data
     * @param string $table
     * @param string $where
     * @param array $params
     * @return int Number of affected rows
     */
    public function delete($table, $where, $params = []) {
        $query = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($query, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Count records
     * @param string $table
     * @param string $where
     * @param array $params
     * @return int
     */
    public function count($table, $where = '', $params = []) {
        $query = "SELECT COUNT(*) as count FROM {$table}";
        if (!empty($where)) {
            $query .= " WHERE {$where}";
        }
        
        $result = $this->fetchOne($query, $params);
        return (int) $result['count'];
    }
    
    /**
     * Check if record exists
     * @param string $table
     * @param string $where
     * @param array $params
     * @return bool
     */
    public function exists($table, $where, $params = []) {
        return $this->count($table, $where, $params) > 0;
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        $this->pdo->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        $this->pdo->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        $this->pdo->rollBack();
    }
    
    /**
     * Get last error info
     * @return array
     */
    public function getErrorInfo() {
        return $this->pdo->errorInfo();
    }
    
    /**
     * Close connection (called automatically)
     */
    public function __destruct() {
        $this->pdo = null;
    }
    
    /**
     * Prevent cloning of singleton
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization of singleton
     */
    public function __wakeup() {}
}

/**
 * Helper function to get database instance quickly
 * @return Database
 */
function db() {
    return Database::getInstance();
}

// Test connection function (remove in production)
function testDatabaseConnection() {
    try {
        $db = Database::getInstance();
        $result = $db->fetchOne("SELECT 'Connection successful!' as message");
        return $result['message'];
    } catch (Exception $e) {
        return "Connection failed: " . $e->getMessage();
    }
}
?>