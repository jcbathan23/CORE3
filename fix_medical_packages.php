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

// Disable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// Drop tables in correct order (child tables first)
if (tableExists($conn, 'package_services')) {
    $conn->query("DROP TABLE IF EXISTS package_services");
    echo "Dropped existing package_services table\n";
}

if (tableExists($conn, 'patient_packages')) {
    $conn->query("DROP TABLE IF EXISTS patient_packages");
    echo "Dropped existing patient_packages table\n";
}

if (tableExists($conn, 'medical_packages')) {
    $conn->query("DROP TABLE IF EXISTS medical_packages");
    echo "Dropped existing medical_packages table\n";
}

// Create medical_packages table
$createTable = "CREATE TABLE `medical_packages` (
    `package_id` int(11) NOT NULL AUTO_INCREMENT,
    `package_name` varchar(255) NOT NULL,
    `description` text NOT NULL,
    `price` decimal(10,2) NOT NULL,
    `duration` int(11) NOT NULL,
    `services` text NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`package_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($createTable)) {
    echo "medical_packages table created successfully\n";
    
    // Create patient_packages table
    $createPatientPackagesTable = "CREATE TABLE `patient_packages` (
        `patient_package_id` int(11) NOT NULL AUTO_INCREMENT,
        `patient_id` int(11) NOT NULL,
        `package_id` int(11) NOT NULL,
        `start_date` date NOT NULL,
        `end_date` date NOT NULL,
        `status` enum('active','completed','cancelled') NOT NULL DEFAULT 'active',
        `notes` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`patient_package_id`),
        KEY `package_id` (`package_id`),
        CONSTRAINT `patient_packages_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `medical_packages` (`package_id`) ON DELETE RESTRICT ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if ($conn->query($createPatientPackagesTable)) {
        echo "patient_packages table created successfully\n";
        
        // Create package_services table
        $createPackageServicesTable = "CREATE TABLE `package_services` (
            `service_id` int(11) NOT NULL AUTO_INCREMENT,
            `patient_package_id` int(11) NOT NULL,
            `service_name` varchar(255) NOT NULL,
            `service_date` date NOT NULL,
            `status` enum('pending','completed','cancelled') NOT NULL DEFAULT 'pending',
            `notes` text DEFAULT NULL,
            `created_by` int(11) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`service_id`),
            KEY `patient_package_id` (`patient_package_id`),
            CONSTRAINT `package_services_ibfk_1` FOREIGN KEY (`patient_package_id`) REFERENCES `patient_packages` (`patient_package_id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        if ($conn->query($createPackageServicesTable)) {
            echo "package_services table created successfully\n";
        } else {
            echo "Error creating package_services table: " . $conn->error . "\n";
        }
    } else {
        echo "Error creating patient_packages table: " . $conn->error . "\n";
    }
    
    // Add sample packages
    $samplePackages = [
        [
            'name' => 'Basic Health Checkup', 
            'desc' => 'Complete physical examination with basic lab tests', 
            'price' => 2500.00, 
            'duration' => 30, 
            'services' => "General physical examination\nBlood pressure check\nBasic blood work\nUrinalysis"
        ],
        [
            'name' => 'Comprehensive Wellness', 
            'desc' => 'Full body checkup with advanced diagnostics and consultations', 
            'price' => 7500.00, 
            'duration' => 60, 
            'services' => "Complete physical examination\nComprehensive blood panel\nECG\nChest X-ray\nAbdominal ultrasound\nDietitian consultation"
        ],
        [
            'name' => 'Cardiac Care', 
            'desc' => 'Specialized cardiac evaluation and monitoring package', 
            'price' => 5000.00, 
            'duration' => 45, 
            'services' => "Cardiac consultation\nECG\nEchocardiogram\nLipid profile\nStress test\nFollow-up visits"
        ]
    ];
    
    $stmt = $conn->prepare("INSERT INTO medical_packages (package_name, description, price, duration, services) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($samplePackages as $package) {
        $stmt->bind_param("ssdis", $package['name'], $package['desc'], $package['price'], $package['duration'], $package['services']);
        if ($stmt->execute()) {
            echo "Added package: " . $package['name'] . "\n";
        } else {
            echo "Error adding package: " . $package['name'] . " - " . $stmt->error . "\n";
        }
    }
    
    $stmt->close();
} else {
    echo "Error creating medical_packages table: " . $conn->error . "\n";
}

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

echo "Database setup completed successfully!\n";
$conn->close();
?> 