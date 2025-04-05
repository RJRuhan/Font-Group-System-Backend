<?php

/**
 * FontGroup model for database operations related to font groups
 */
class FontGroup {
    private $conn;
    private $table_name = "font_groups";
    private $items_table = "font_group_items";
    
    // Font group properties
    public $id;
    public $name;
    public $created_at;
    
    /**
     * Constructor with DB connection
     * @param PDO $db
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Create new font group
     * @param array $fonts Array of font names
     * @return boolean
     */
    public function create($fonts) {
        // Start transaction
        $this->conn->beginTransaction();
        
        try {
            // Insert into font_groups table
            $query = "INSERT INTO " . $this->table_name . " 
                      SET name = :name, created_at = NOW()";
            
            $stmt = $this->conn->prepare($query);
            
            // Sanitize inputs
            $this->name = htmlspecialchars(strip_tags($this->name));
            
            // Bind values
            $stmt->bindParam(":name", $this->name);
            $stmt->execute();
            
            $group_id = $this->conn->lastInsertId();
            
            // Insert into font_group_items table
            foreach($fonts as $font_name) {
                $query = "INSERT INTO " . $this->items_table . " 
                          SET group_id = :group_id, font_name = :font_name";
                
                $stmt = $this->conn->prepare($query);
                
                // Bind values
                $stmt->bindParam(":group_id", $group_id);
                $stmt->bindParam(":font_name", $font_name);
                $stmt->execute();
            }
            
            // Commit transaction
            $this->conn->commit();
            
            $this->id = $group_id;
            return true;
        } catch(Exception $e) {
            // Roll back transaction if any error
            $this->conn->rollBack();
            return false;
        }
    }
    
    /**
     * Read all font groups with their fonts
     * @return array
     */
    public function readAll() {
        $query = "SELECT g.id, g.name, g.created_at 
                  FROM " . $this->table_name . " g 
                  ORDER BY g.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $groups = [];
        
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $font_query = "SELECT i.font_name, f.file_path 
                           FROM " . $this->items_table . " i
                           JOIN fonts f ON i.font_name = f.name
                           WHERE i.group_id = ?";
            
            $font_stmt = $this->conn->prepare($font_query);
            $font_stmt->bindParam(1, $row['id']);
            $font_stmt->execute();
            
            $fonts = [];
            while($font_row = $font_stmt->fetch(PDO::FETCH_ASSOC)) {
                $fonts[] = [
                    'name' => $font_row['font_name'],
                    'file_path' => $font_row['file_path']
                ];
            }
            
            $groups[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'fonts' => $fonts
            ];
        }
        
        return $groups;
    }
    
    /**
     * Check if font group exists by name
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
     * Delete font group by name
     * @param string $name
     * @return boolean
     */
    public function delete($name) {
        // Start transaction
        $this->conn->beginTransaction();
        
        try {
            // Get group ID
            $query = "SELECT id FROM " . $this->table_name . " WHERE name = ? LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $name);
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(!$row) {
                return false; // Group not found
            }
            
            $group_id = $row['id'];
            
            // Delete from font_group_items
            $query = "DELETE FROM " . $this->items_table . " WHERE group_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $group_id);
            $stmt->execute();
            
            // Delete from font_groups
            $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $group_id);
            $stmt->execute();
            
            // Commit transaction
            $this->conn->commit();
            
            return true;
        } catch(Exception $e) {
            // Roll back transaction if any error
            $this->conn->rollBack();
            return false;
        }
    }
    
    /**
     * Update font group
     * @param string $old_name The original group name
     * @param array $fonts Array of font names
     * @return boolean
     */
    public function update($old_name, $fonts) {
        // Start transaction
        $this->conn->beginTransaction();
        
        try {
            // Get group ID
            $query = "SELECT id FROM " . $this->table_name . " WHERE name = ? LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $old_name);
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(!$row) {
                return false; // Group not found
            }
            
            $group_id = $row['id'];
            
            // Update group name if it has changed
            if($old_name !== $this->name) {
                $query = "UPDATE " . $this->table_name . " SET name = :name WHERE id = :id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":name", $this->name);
                $stmt->bindParam(":id", $group_id);
                $stmt->execute();
            }
            
            // Delete existing font_group_items
            $query = "DELETE FROM " . $this->items_table . " WHERE group_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $group_id);
            $stmt->execute();
            
            // Insert new font_group_items
            foreach($fonts as $font_name) {
                $query = "INSERT INTO " . $this->items_table . " 
                          SET group_id = :group_id, font_name = :font_name";
                
                $stmt = $this->conn->prepare($query);
                
                // Bind values
                $stmt->bindParam(":group_id", $group_id);
                $stmt->bindParam(":font_name", $font_name);
                $stmt->execute();
            }
            
            // Commit transaction
            $this->conn->commit();
            
            return true;
        } catch(Exception $e) {
            // Roll back transaction if any error
            $this->conn->rollBack();
            return false;
        }
    }
}
