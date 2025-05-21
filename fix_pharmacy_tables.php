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

// Function to check if a column exists in a table
function columnExists($conn, $tableName, $columnName) {
    $result = $conn->query("SHOW COLUMNS FROM $tableName LIKE '$columnName'");
    return $result->num_rows > 0;
}

// Create or update pharmacy_inventory table
if (!tableExists($conn, 'pharmacy_inventory')) {
    $create_inventory = "CREATE TABLE pharmacy_inventory (
        medicine_id INT AUTO_INCREMENT PRIMARY KEY,
        medicine_name VARCHAR(255) NOT NULL,
        category ENUM('tablet', 'capsule', 'syrup', 'injection', 'cream') NOT NULL DEFAULT 'tablet',
        quantity INT NOT NULL DEFAULT 0,
        unit VARCHAR(50) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        manufacturer VARCHAR(255) NOT NULL,
        expiry_date DATE NOT NULL,
        storage_location VARCHAR(255) NOT NULL,
        status VARCHAR(20) DEFAULT 'In Stock'
    )";
    
    if ($conn->query($create_inventory)) {
        echo "pharmacy_inventory table created successfully\n";
    } else {
        echo "Error creating pharmacy_inventory table: " . $conn->error . "\n";
    }
} else {
    // Check and add category column if it doesn't exist
    if (!columnExists($conn, 'pharmacy_inventory', 'category')) {
        $add_category = "ALTER TABLE pharmacy_inventory 
                        ADD COLUMN category ENUM('tablet', 'capsule', 'syrup', 'injection', 'cream') 
                        NOT NULL DEFAULT 'tablet'";
        if ($conn->query($add_category)) {
            echo "Category column added to pharmacy_inventory\n";
            
            // Update existing records with random categories
            $update_categories = "UPDATE pharmacy_inventory 
                                SET category = ELT(FLOOR(1 + RAND() * 5), 
                                'tablet', 'capsule', 'syrup', 'injection', 'cream')";
            $conn->query($update_categories);
        } else {
            echo "Error adding category column: " . $conn->error . "\n";
        }
    }
}

// Create or update pharmacy_sales table
if (!tableExists($conn, 'pharmacy_sales')) {
    $create_sales = "CREATE TABLE pharmacy_sales (
        sale_id INT AUTO_INCREMENT PRIMARY KEY,
        prescription_id INT,
        patient_name VARCHAR(255) NOT NULL,
        sale_date DATE NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(50) NOT NULL,
        created_by VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_sales)) {
        echo "pharmacy_sales table created successfully\n";
    } else {
        echo "Error creating pharmacy_sales table: " . $conn->error . "\n";
    }
}

// Create or update pharmacy_sale_items table
if (!tableExists($conn, 'pharmacy_sale_items')) {
    $create_sale_items = "CREATE TABLE pharmacy_sale_items (
        item_id INT AUTO_INCREMENT PRIMARY KEY,
        sale_id INT NOT NULL,
        medicine_id INT NOT NULL,
        medicine_name VARCHAR(255) NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        total_price DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (sale_id) REFERENCES pharmacy_sales(sale_id),
        FOREIGN KEY (medicine_id) REFERENCES pharmacy_inventory(medicine_id)
    )";
    
    if ($conn->query($create_sale_items)) {
        echo "pharmacy_sale_items table created successfully\n";
    } else {
        echo "Error creating pharmacy_sale_items table: " . $conn->error . "\n";
    }
}

// Insert sample data if pharmacy_inventory is empty
$check_inventory = $conn->query("SELECT COUNT(*) as count FROM pharmacy_inventory");
$row = $check_inventory->fetch_assoc();

if ($row['count'] == 0) {
    $sample_data = "INSERT INTO pharmacy_inventory 
                    (medicine_name, category, quantity, unit, price, manufacturer, expiry_date, storage_location) 
                    VALUES 
                    ('Paracetamol', 'tablet', 100, 'tablets', 5.00, 'Generic Co', '2025-12-31', 'Shelf A1'),
                    ('Amoxicillin', 'capsule', 200, 'capsules', 10.00, 'PharmaCo', '2025-12-31', 'Shelf B2'),
                    ('Cough Syrup', 'syrup', 50, 'bottles', 15.00, 'MediCo', '2025-12-31', 'Shelf C3')";
    
    if ($conn->query($sample_data)) {
        echo "Sample inventory data inserted successfully\n";
    } else {
        echo "Error inserting sample data: " . $conn->error . "\n";
    }
}

// Insert sample sales data if pharmacy_sales is empty
$check_sales = $conn->query("SELECT COUNT(*) as count FROM pharmacy_sales");
$row = $check_sales->fetch_assoc();

if ($row['count'] == 0) {
    // First insert a sale
    $sample_sale = "INSERT INTO pharmacy_sales 
                    (patient_name, sale_date, total_amount, payment_method, created_by) 
                    VALUES 
                    ('John Doe', CURDATE(), 20.00, 'Cash', 'Admin')";
    
    if ($conn->query($sample_sale)) {
        $sale_id = $conn->insert_id;
        
        // Then insert sale items
        $sample_items = "INSERT INTO pharmacy_sale_items 
                        (sale_id, medicine_id, medicine_name, quantity, unit_price, total_price)
                        SELECT $sale_id, medicine_id, medicine_name, 1, price, price
                        FROM pharmacy_inventory LIMIT 1";
        
        if ($conn->query($sample_items)) {
            echo "Sample sales data inserted successfully\n";
        } else {
            echo "Error inserting sample sale items: " . $conn->error . "\n";
        }
    } else {
        echo "Error inserting sample sale: " . $conn->error . "\n";
    }
}

echo "Database setup completed successfully!\n";
$conn->close();
?> 