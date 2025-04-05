<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/FontGroup.php';
require_once __DIR__ . '/../models/Font.php';
/**
 * Controller for font group operations
 */
class FontGroupController {
    private $db;
    private $fontGroup;
    private $font;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Create database connection
        // $database = new Database();
        // $this->db = $database->getConnection();

        $this->db = Database::getInstance()->getConnection();
        
        // Initialize models
        $this->fontGroup = new FontGroup($this->db);
        $this->font = new Font($this->db);
    }
    
    /**
     * Create font group
     * @param array $data Group data including name and fonts
     * @return array Response with status and message
     */
    public function createGroup() {
        $response = array(
            'status' => false,
            'message' => ''
        );

        // Get input data
        $input = file_get_contents("php://input");
        $data = json_decode($input, true); // decode as associative array

        // Validate input
        if(!isset($data['name']) || empty(trim($data['name']))) {
            $response['message'] = 'Group name is required';
            return $response;
        }
        
        if(!isset($data['fonts']) || !is_array($data['fonts']) || count($data['fonts']) < 2) {
            $response['message'] = 'At least two fonts must be selected';
            return $response;
        }
        
        // Check if group name already exists
        if($this->fontGroup->existsByName($data['name'])) {
            $response['message'] = 'Font group with this name already exists';
            return $response;
        }
        
        // Validate that all fonts exist
        $font_names = array();
        foreach($data['fonts'] as $font) {
            if(!isset($font['name']) || empty($font['name'])) {
                continue;
            }
            
            if(!$this->font->getByName($font['name'])) {
                $response['message'] = 'One or more selected fonts do not exist';
                return $response;
            }
            
            $font_names[] = $font['name'];
        }
        
        // Ensure we have at least 2 unique fonts
        $unique_fonts = array_unique($font_names);
        if(count($unique_fonts) < 2) {
            $response['message'] = 'At least two different fonts must be selected';
            return $response;
        }
        
        // Set fontGroup properties
        $this->fontGroup->name = $data['name'];
        
        // Create font group
        if($this->fontGroup->create($unique_fonts)) {
            $response['status'] = true;
            $response['message'] = 'Font group created successfully';
        } else {
            $response['message'] = 'Failed to create font group';
        }
        
        return $response;
    }
    
    /**
     * Get all font groups
     * @return array Response with status and data
     */
    public function getAllGroups() {
        $response = array(
            'status' => false,
            'message' => '',
            'data' => array()
        );
        
        $groups = $this->fontGroup->readAll();
        
        if(!empty($groups)) {
            $response['status'] = true;
            $response['message'] = 'Font groups retrieved successfully';
            $response['data'] = $groups;
        } else {
            $response['message'] = 'No font groups found';
        }
        
        return $response;
    }
    
    /**
     * Delete font group
     * @param string $name Group name
     * @return array Response with status and message
     */
    public function deleteGroup($groupName) {
        $response = array(
            'status' => false,
            'message' => ''
        );
        
        if($this->fontGroup->delete($groupName)) {
            $response['status'] = true;
            $response['message'] = 'Font group deleted successfully';
        } else {
            $response['message'] = 'Failed to delete font group';
        }
        
        return $response;
    }
    
    /**
     * Update font group
     * @param string $name Original group name
     * @param array $data Updated group data
     * @return array Response with status and message
     */
    public function updateGroup($groupName) {
        $response = array(
            'status' => false,
            'message' => ''
        );

        // Get input data
        $input = file_get_contents("php://input");
        $data = json_decode($input, true); // decode as associative array
        
        // Validate input
        if(!isset($data['name']) || empty(trim($data['name']))) {
            $response['message'] = 'Group name is required';
            return $response;
        }
        
        if(!isset($data['fonts']) || !is_array($data['fonts']) || count($data['fonts']) < 2) {
            $response['message'] = 'At least two fonts must be selected';
            return $response;
        }
        
        // Check if new group name already exists (unless it's the same as current name)
        if($data['name'] !== $groupName && $this->fontGroup->existsByName($data['name'])) {
            $response['message'] = 'Font group with this name already exists';
            return $response;
        }
        
        // Validate that all fonts exist
        $font_names = array();
        foreach($data['fonts'] as $font) {
            if(!isset($font['name']) || empty($font['name'])) {
                continue;
            }
            
            if(!$this->font->getByName($font['name'])) {
                $response['message'] = 'One or more selected fonts do not exist';
                return $response;
            }
            
            $font_names[] = $font['name'];
        }
        
        // Ensure we have at least 2 unique fonts
        $unique_fonts = array_unique($font_names);
        if(count($unique_fonts) < 2) {
            $response['message'] = 'At least two different fonts must be selected';
            return $response;
        }
        
        // Set fontGroup properties
        $this->fontGroup->name = $data['name'];
        
        // Update font group
        if($this->fontGroup->update($groupName, $unique_fonts)) {
            $response['status'] = true;
            $response['message'] = 'Font group updated successfully';
        } else {
            $response['message'] = 'Failed to update font group';
        }
        
        return $response;
    }
}
