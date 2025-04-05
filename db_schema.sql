

-- Create the database
CREATE DATABASE IF NOT EXISTS font_management_system;
USE font_management_system;

-- Create fonts table
CREATE TABLE IF NOT EXISTS fonts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    file_path VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Create font_groups table
CREATE TABLE IF NOT EXISTS font_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Create font_group_items table for many-to-many relationship
CREATE TABLE IF NOT EXISTS font_group_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    font_name VARCHAR(255) NOT NULL,
    FOREIGN KEY (group_id) REFERENCES font_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (font_name) REFERENCES fonts(name) ON DELETE CASCADE,
    UNIQUE KEY unique_group_font (group_id, font_name)
);
