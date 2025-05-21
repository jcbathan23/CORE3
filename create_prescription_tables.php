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

// Create pharmacy_prescriptions table
$create_prescriptions = "CREATE TABLE IF NOT EXISTS pharmacy_prescriptions (
    prescription_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_name VARCHAR(255) NOT NULL,
    doctor VARCHAR(255) NOT NULL,
    prescription_date DATE NOT NULL,
    status ENUM('pending', 'filled', 'rejected') DEFAULT 'pending',
    dispensed_by VARCHAR(255),
    dispensing_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($create_prescriptions)) {
    echo "pharmacy_prescriptions table created successfully\n";
} else {
    echo "Error creating pharmacy_prescriptions table: " . $conn->error . "\n";
}

// Create pharmacy_prescription_items table
$create_prescription_items = "CREATE TABLE IF NOT EXISTS pharmacy_prescription_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT NOT NULL,
    medicine_id INT NOT NULL,
    medicine_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    instructions TEXT,
    FOREIGN KEY (prescription_id) REFERENCES pharmacy_prescriptions(prescription_id),
    FOREIGN KEY (medicine_id) REFERENCES pharmacy_inventory(medicine_id)
)";

if ($conn->query($create_prescription_items)) {
    echo "pharmacy_prescription_items table created successfully\n";
} else {
    echo "Error creating pharmacy_prescription_items table: " . $conn->error . "\n";
}

// Create pharmacy_sales table
$create_sales = "CREATE TABLE IF NOT EXISTS pharmacy_sales (
    sale_id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT,
    patient_name VARCHAR(255) NOT NULL,
    sale_date DATE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    created_by VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prescription_id) REFERENCES pharmacy_prescriptions(prescription_id)
)";

if ($conn->query($create_sales)) {
    echo "pharmacy_sales table created successfully\n";
} else {
    echo "Error creating pharmacy_sales table: " . $conn->error . "\n";
}

// Create pharmacy_sale_items table
$create_sale_items = "CREATE TABLE IF NOT EXISTS pharmacy_sale_items (
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

// Insert sample prescription data
$insert_sample = "INSERT INTO pharmacy_prescriptions (patient_name, doctor, prescription_date, status) 
                 VALUES 
                 ('John Doe', 'Dr. Smith', CURDATE(), 'pending'),
                 ('Jane Smith', 'Dr. Johnson', CURDATE(), 'pending')";

if ($conn->query($insert_sample)) {
    echo "Sample prescriptions added successfully\n";
} else {
    echo "Error adding sample prescriptions: " . $conn->error . "\n";
}

$conn->close();
?> 