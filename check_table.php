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

// Check if table exists
$check_table = "SHOW TABLES LIKE 'pharmacy_inventory'";
$result = $conn->query($check_table);

if ($result->num_rows > 0) {
    echo "Table pharmacy_inventory exists\n";
    
    // Get table structure
    $structure = "DESCRIBE pharmacy_inventory";
    $result = $conn->query($structure);
    
    if ($result) {
        echo "\nTable structure:\n";
        while ($row = $result->fetch_assoc()) {
            echo $row['Field'] . " - " . $row['Type'] . " - " . $row['Null'] . " - " . $row['Default'] . "\n";
        }
    } else {
        echo "Error getting table structure: " . $conn->error;
    }
} else {
    echo "Table pharmacy_inventory does not exist\n";
    
    // Create the table if it doesn't exist
    $create_table = "CREATE TABLE pharmacy_inventory (
        medicine_id INT AUTO_INCREMENT PRIMARY KEY,
        medicine_name VARCHAR(255) NOT NULL,
        category VARCHAR(50) NOT NULL,
        quantity INT NOT NULL,
        unit VARCHAR(50) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        manufacturer VARCHAR(255) NOT NULL,
        expiry_date DATE NOT NULL,
        storage_location VARCHAR(255) NOT NULL,
        status VARCHAR(20) DEFAULT 'In Stock'
    )";
    
    if ($conn->query($create_table) === TRUE) {
        echo "Table pharmacy_inventory created successfully";
    } else {
        echo "Error creating table: " . $conn->error;
    }
}

$conn->close();
?> 