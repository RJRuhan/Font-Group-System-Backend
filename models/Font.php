
<?php

/**
 * Font model for database operations related to fonts
 */
class Font {
    private $conn;
    private $table_name = "fonts";
    
    // Font properties
    public $id;
    public $name;
    public $file_path;
    public $created_at;
    
    /**
     * Constructor with DB connection
     * @param PDO $db
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Create new font record
     * @return boolean
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET name = :name, file_path = :file_path, created_at = NOW()";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->file_path = htmlspecialchars(strip_tags($this->file_path));
        
        // Bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":file_path", $this->file_path);
        
        // Execute query
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    /**
     * Read all fonts
     * @return PDOStatement
     */
    public function readAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * Check if font exists by name
     * @param string $name
     * @return boolean
     */
    public function existsByName($name) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE name = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $name);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['count'] > 0;
    }
    
    /**
     * Delete font by name
     * @param string $name
     * @return boolean
     */
    public function delete($name) {
        // First check if font is used in any group
        $query = "SELECT COUNT(*) as count FROM font_group_items WHERE font_name = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $name);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row['count'] > 0) {
            return false; // Font is in use, cannot delete
        }
        
        // Get the file path to delete the physical file
        $query = "SELECT file_path FROM " . $this->table_name . " WHERE name = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $name);
        $stmt->execute();
        $font = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Delete from database
        $query = "DELETE FROM " . $this->table_name . " WHERE name = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $name);
        
        if($stmt->execute() && $font) {
            // Also delete the physical file
            if(file_exists($font['file_path'])) {
                unlink($font['file_path']);
            }
            return true;
        }
        
        return false;
    }
    
    /**
     * Get font by name
     * @param string $name
     * @return array
     */
    public function getByName($name) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE name = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $name);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
