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

// Function to check if a table exists
function tableExists($conn, $tableName) {
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    return $result->num_rows > 0;
}

// Create pharmacy_suppliers table if it doesn't exist
if (!tableExists($conn, 'pharmacy_suppliers')) {
    $create_suppliers = "CREATE TABLE pharmacy_suppliers (
        supplier_id INT AUTO_INCREMENT PRIMARY KEY,
        company_name VARCHAR(255) NOT NULL,
        contact_person VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(50) NOT NULL,
        address TEXT NOT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_suppliers)) {
        echo "pharmacy_suppliers table created successfully\n";
        
        // Insert sample supplier data
        $sample_suppliers = "INSERT INTO pharmacy_suppliers 
            (company_name, contact_person, email, phone, address) VALUES 
            ('PharmaCorp Inc.', 'John Smith', 'john@pharmacorp.com', '+1234567890', '123 Pharma St.'),
            ('MediSupply Co.', 'Jane Doe', 'jane@medisupply.com', '+0987654321', '456 Medi Ave.')";
        
        if ($conn->query($sample_suppliers)) {
            echo "Sample supplier data inserted successfully\n";
        }
    } else {
        echo "Error creating pharmacy_suppliers table: " . $conn->error . "\n";
    }
}

// Create pharmacy_orders table if it doesn't exist
if (!tableExists($conn, 'pharmacy_orders')) {
    $create_orders = "CREATE TABLE pharmacy_orders (
        order_id INT AUTO_INCREMENT PRIMARY KEY,
        supplier_id INT NOT NULL,
        order_date DATE NOT NULL,
        expected_delivery_date DATE NOT NULL,
        status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
        priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (supplier_id) REFERENCES pharmacy_suppliers(supplier_id)
    )";
    
    if ($conn->query($create_orders)) {
        echo "pharmacy_orders table created successfully\n";
        
        // Insert sample order data
        $sample_orders = "INSERT INTO pharmacy_orders 
            (supplier_id, order_date, expected_delivery_date, status, priority, notes) 
            SELECT 
                supplier_id,
                CURDATE(),
                DATE_ADD(CURDATE(), INTERVAL 7 DAY),
                'pending',
                'medium',
                'Sample order for testing'
            FROM pharmacy_suppliers LIMIT 1";
        
        if ($conn->query($sample_orders)) {
            echo "Sample order data inserted successfully\n";
        }
    } else {
        echo "Error creating pharmacy_orders table: " . $conn->error . "\n";
    }
}

// Create pharmacy_order_items table if it doesn't exist
if (!tableExists($conn, 'pharmacy_order_items')) {
    $create_order_items = "CREATE TABLE pharmacy_order_items (
        item_id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        medicine_id INT NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        total_price DECIMAL(10,2) NOT NULL,
        notes TEXT,
        FOREIGN KEY (order_id) REFERENCES pharmacy_orders(order_id),
        FOREIGN KEY (medicine_id) REFERENCES pharmacy_inventory(medicine_id)
    )";
    
    if ($conn->query($create_order_items)) {
        echo "pharmacy_order_items table created successfully\n";
        
        // Insert sample order items
        $sample_items = "INSERT INTO pharmacy_order_items 
            (order_id, medicine_id, quantity, unit_price, total_price) 
            SELECT 
                (SELECT order_id FROM pharmacy_orders LIMIT 1),
                medicine_id,
                10,
                10.00,  -- Fixed unit price for sample data
                100.00  -- Fixed total price for sample data
            FROM pharmacy_inventory LIMIT 3";
        
        if ($conn->query($sample_items)) {
            echo "Sample order items inserted successfully\n";
        }
    } else {
        echo "Error creating pharmacy_order_items table: " . $conn->error . "\n";
    }
}

echo "Database setup completed successfully!\n";
$conn->close();
?> 