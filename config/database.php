
<?php

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
/**
 * Database configuration and connection handler (Singleton)
 */
class Database {
    private static $instance = null;
    private $conn;
    
    private $host;
    private $db_name;
    private $username;
    private $password;

    private function __construct() {
        // ✅ Assign env values here
        $this->host     = $_ENV['DB_HOST'];
        $this->db_name  = $_ENV['DB_DATABASE'];
        $this->username = $_ENV['DB_USERNAME'];
        $this->password = $_ENV['DB_PASSWORD'];

        try {
            $this->conn = new PDO(
                'mysql:host=' . $this->host . ';dbname=' . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("SET NAMES utf8");
        } catch (PDOException $exception) {
            die('Connection error: ' . $exception->getMessage());
        }
    }

    /**
     * Get the single instance of Database
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Get the database connection
     * @return PDO
     */
    public function getConnection() {
        return $this->conn;
    }

    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}

    /**
     * Prevent unserializing of the instance
     */
    public function __wakeup() {}
}
