<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Font.php';

/**
 * Controller for font operations
 */
class FontController {
    private $db;
    private $font;
    private $upload_dir = __DIR__ . '/../uploads/fonts/'; // Directory to store uploaded fonts
    
    /**
     * Constructor
     */
    public function __construct() {
        // Create database connection
        $this->db = Database::getInstance()->getConnection();

        // $database = new Database();
        // $this->db = $database->getConnection();
        
        // Initialize font model
        $this->font = new Font($this->db);
        
        // Ensure upload directory exists
        if(!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0777, true);
        }
    }
    
    /**
     * Upload new font
     * @return array Response with status and data
     */
    public function upload() {
        $response = array(
            'status' => false,
            'message' => '',
            'data' => null
        );
        
        // Check if file was uploaded
        if(!isset($_FILES['font']) || $_FILES['font']['error'] !== UPLOAD_ERR_OK) {
            $response['message'] = 'No file uploaded or upload error';
            return $response;
        }
        
        $file = $_FILES['font'];
        
        // Check file type (only TTF allowed)
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if($file_extension !== 'ttf') {
            $response['message'] = 'Only TTF files are allowed';
            return $response;
        }
        
        // Generate safe file name
        $font_name = pathinfo($file['name'], PATHINFO_FILENAME);
        
        // Check if font with same name already exists
        if($this->font->existsByName($font_name)) {
            $response['message'] = 'Font with this name already exists';
            return $response;
        }
        
        // Generate unique filename
        $unique_filename = uniqid() . '.ttf';
        $upload_path = $this->upload_dir . $unique_filename;
        
        // Move uploaded file
        if(move_uploaded_file($file['tmp_name'], $upload_path)) {
            // Set font properties
            $this->font->name = $font_name;
            $this->font->file_path = $upload_path;
            
            // Create font record
            if($this->font->create()) {
                $response['status'] = true;
                $response['message'] = 'Font uploaded successfully';
                $response['data'] = array(
                    'name' => $font_name,
                    'file' => $file,
                    'url' => $upload_path
                );
            } else {
                // Delete uploaded file if database insertion fails
                unlink($upload_path);
                $response['message'] = 'Failed to save font record';
            }
        } else {
            $response['message'] = 'Failed to upload file';
        }
        
        return $response;
    }
    
    /**
     * Get all fonts
     * @return array Response with status and data
     */
    public function getAllFonts() {
        $response = array(
            'status' => false,
            'message' => '',
            'data' => array()
        );
        
        $stmt = $this->font->readAll();
        
        if($stmt->rowCount() > 0) {
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Read the file content if the file exists
                $file_content = null;
                if(file_exists($row['file_path'])) {
                    $file_content = base64_encode(file_get_contents($row['file_path']));
                }
                
                $response['data'][] = array(
                    'name' => $row['name'],
                    'file' => $file_content, // Include the file content as base64 encoded string
                    'url' => null // No longer including the URL since we're sending the file
                );
            }
            
            $response['status'] = true;
            $response['message'] = 'Fonts retrieved successfully';
        } else {
            $response['message'] = 'No fonts found';
        }
        
        return $response;
    }
    
    /**
     * Delete font
     * @param string $name Font name
     * @return array Response with status and message
     */
    public function deleteFont($fontName) {
        $response = array(
            'status' => false,
            'message' => ''
        );
        
        if($this->font->delete($fontName)) {
            $response['status'] = true;
            $response['message'] = 'Font deleted successfully';
        } else {
            $response['message'] = 'Cannot delete font that is in a group';
        }
        
        return $response;
    }
}