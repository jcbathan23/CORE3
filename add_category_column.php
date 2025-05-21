<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "core3";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add category column if it doesn't exist
$check_column = "SHOW COLUMNS FROM pharmacy_inventory LIKE 'category'";
$result = $conn->query($check_column);

if ($result->num_rows == 0) {
    // Add category column
    $add_column = "ALTER TABLE pharmacy_inventory 
                   ADD COLUMN category ENUM('tablet', 'capsule', 'syrup', 'injection', 'cream') NOT NULL DEFAULT 'tablet'";
    
    if ($conn->query($add_column)) {
        echo "Category column added successfully\n";
        
        // Update existing records with random categories for testing
        $update_categories = "UPDATE pharmacy_inventory 
                            SET category = ELT(FLOOR(1 + RAND() * 5), 
                            'tablet', 'capsule', 'syrup', 'injection', 'cream')";
        
        if ($conn->query($update_categories)) {
            echo "Existing records updated with random categories\n";
        } else {
            echo "Error updating existing records: " . $conn->error . "\n";
        }
    } else {
        echo "Error adding category column: " . $conn->error . "\n";
    }
} else {
    echo "Category column already exists\n";
}

$conn->close();
?> 