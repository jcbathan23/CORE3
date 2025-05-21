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

// SQL to add status column
$sql = "ALTER TABLE pharmacy_inventory ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'In Stock'";

if ($conn->query($sql) === TRUE) {
    echo "Status column added successfully";
} else {
    echo "Error adding status column: " . $conn->error;
}

// Update existing records with status based on quantity
$update_sql = "UPDATE pharmacy_inventory SET 
               status = CASE 
                   WHEN quantity <= 0 THEN 'Out of Stock'
                   WHEN quantity <= 50 THEN 'Low Stock'
                   ELSE 'In Stock'
               END";

if ($conn->query($update_sql) === TRUE) {
    echo "\nExisting records updated successfully";
} else {
    echo "\nError updating records: " . $conn->error;
}

$conn->close();
?> 