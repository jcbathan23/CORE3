<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: loginDefault.php");
    exit();
}

// Include database connection
include 'config.php';

// Check if patient_packages table exists, if not create it
$tableCheck = $conn->query("SHOW TABLES LIKE 'patient_packages'");
if ($tableCheck->num_rows == 0) {
    $createTable = "CREATE TABLE `patient_packages` (
      `patient_package_id` int(11) NOT NULL AUTO_INCREMENT,
      `patient_id` int(11) NOT NULL,
      `package_id` int(11) NOT NULL,
      `start_date` date NOT NULL,
      `end_date` date NOT NULL,
      `status` enum('active','completed','cancelled') NOT NULL DEFAULT 'active',
      `notes` text DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`patient_package_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    $conn->query($createTable);
}

// Check if patients table exists, if not create it
$tableCheck = $conn->query("SHOW TABLES LIKE 'patients'");
if ($tableCheck->num_rows == 0) {
    $createTable = "CREATE TABLE `patients` (
      `patient_id` int(11) NOT NULL AUTO_INCREMENT,
      `first_name` varchar(255) NOT NULL,
      `last_name` varchar(255) NOT NULL,
      `contact_number` varchar(20) DEFAULT NULL,
      `email` varchar(255) DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`patient_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    $conn->query($createTable);
    
    // Add some sample patients
    $conn->query("INSERT INTO patients (first_name, last_name, contact_number, email) VALUES 
                 ('John', 'Doe', '+1234567890', 'john@example.com'),
                 ('Jane', 'Smith', '+0987654321', 'jane@example.com'),
                 ('Robert', 'Johnson', '+5556667777', 'robert@example.com'),
                 ('Maria', 'Garcia', '+8889990000', 'maria@example.com')");
}

// Process form submission for assigning package
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['update_status'])) {
    $patient_id = $_POST['patient_id'];
    $package_id = $_POST['package_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = $_POST['status'];
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert into patient_packages
        $stmt = $conn->prepare("INSERT INTO patient_packages (patient_id, package_id, start_date, end_date, status, notes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissss", $patient_id, $package_id, $start_date, $end_date, $status, $notes);
        
        if ($stmt->execute()) {
            $patient_package_id = $conn->insert_id;
            
            // Get package services
            $servicesStmt = $conn->prepare("SELECT services FROM medical_packages WHERE package_id = ?");
            $servicesStmt->bind_param("i", $package_id);
            $servicesStmt->execute();
            $result = $servicesStmt->get_result();
            $package = $result->fetch_assoc();
            
            if ($package) {
                // Split services into array
                $services = explode("\n", $package['services']);
                
                // Prepare statement for inserting services
                $serviceInsertStmt = $conn->prepare("INSERT INTO package_services (patient_package_id, service_name, service_date, status, created_by) VALUES (?, ?, ?, 'pending', ?)");
                
                // Insert each service
                foreach ($services as $service) {
                    if (trim($service) !== '') {
                        $serviceInsertStmt->bind_param("issi", $patient_package_id, $service, $start_date, $_SESSION['user_id']);
                        $serviceInsertStmt->execute();
                    }
                }
                
                $serviceInsertStmt->close();
            }
            
            $servicesStmt->close();
            $conn->commit();
            $success_message = "Package assigned successfully with all services!";
        } else {
            throw new Exception($stmt->error);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error: " . $e->getMessage();
    }
}

// Process form submission for updating package status
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $patient_package_id = $_POST['patient_package_id'];
    $status = $_POST['status'];
    $end_date = $_POST['end_date'];
    
    // Prepare and execute SQL statement
    $stmt = $conn->prepare("UPDATE patient_packages SET status = ?, end_date = ? WHERE patient_package_id = ?");
    $stmt->bind_param("ssi", $status, $end_date, $patient_package_id);
    
    if ($stmt->execute()) {
        $success_message = "Package status updated successfully!";
    } else {
        $error_message = "Error: " . $stmt->error;
    }
    
    $stmt->close();
}

// Verify that all required tables exist
$requiredTables = ['patient_packages', 'patients', 'medical_packages'];
$missingTables = [];
$tableInfo = [];

foreach ($requiredTables as $table) {
    $tableCheck = $conn->query("SHOW TABLES LIKE '$table'");
    if ($tableCheck->num_rows == 0) {
        $missingTables[] = $table;
    } else {
        // Get table structure
        $columns = $conn->query("SHOW COLUMNS FROM $table");
        $tableInfo[$table] = [];
        while ($column = $columns->fetch_assoc()) {
            $tableInfo[$table][] = $column['Field'];
        }
    }
}

if (!empty($missingTables)) {
    $error_message = "Error: The following tables are missing: " . implode(", ", $missingTables);
}

// Only fetch data if all tables exist
if (empty($missingTables)) {
    // Check if the required columns exist in each table
    $hasFirstName = in_array('first_name', $tableInfo['patients'] ?? []);
    $hasLastName = in_array('last_name', $tableInfo['patients'] ?? []);
    $hasPackageName = in_array('package_name', $tableInfo['medical_packages'] ?? []);
    
    // Fetch all patient packages
    $assignments = [];
    $query = "
        SELECT pp.*";
    
    // Use first_name and last_name instead of patient_name
    if ($hasFirstName && $hasLastName) {
        $query .= ", CONCAT(p.first_name, ' ', p.last_name) as patient_name";
    } else if ($hasFirstName) {
        $query .= ", p.first_name as patient_name";
    } else if ($hasLastName) {
        $query .= ", p.last_name as patient_name";
    } else {
        $query .= ", 'Unknown Patient' as patient_name";
    }
    
    // Only add package_name to the query if it exists
    if ($hasPackageName) {
        $query .= ", mp.package_name";
    } else {
        $query .= ", 'Unknown Package' as package_name";
    }
    
    $query .= " 
        FROM patient_packages pp
        LEFT JOIN patients p ON pp.patient_id = p.patient_id
        LEFT JOIN medical_packages mp ON pp.package_id = mp.package_id
        ORDER BY pp.start_date DESC
    ";

    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $assignments[] = $row;
        }
    } else {
        $error_message = "Error fetching patient packages: " . $conn->error;
    }

    // Fetch all patients for the dropdown
    $patients = [];
    $patientQuery = "SELECT patient_id";
    
    // Check if first_name and last_name columns exist
    if ($hasFirstName && $hasLastName) {
        $patientQuery .= ", first_name, last_name, CONCAT(first_name, ' ', last_name) as full_name FROM patients ORDER BY first_name, last_name";
    } else if ($hasFirstName) {
        $patientQuery .= ", first_name as full_name FROM patients ORDER BY first_name";
    } else if ($hasLastName) {
        $patientQuery .= ", last_name as full_name FROM patients ORDER BY last_name";
    } else {
        $patientQuery .= " FROM patients ORDER BY patient_id";
    }
    
    $result = $conn->query($patientQuery);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $patients[] = $row;
        }
    } else {
        $error_message = "Error fetching patients: " . $conn->error;
    }

    // Fetch all packages for the dropdown
    $packages = [];
    $packageQuery = "SELECT * FROM medical_packages";
    
    // Check if package_name column exists
    if ($hasPackageName) {
        $packageQuery .= " ORDER BY package_name";
    } else {
        $packageQuery .= " ORDER BY package_id";
    }
    
    $result = $conn->query($packageQuery);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $packages[] = $row;
        }
    } else {
        $error_message = "Error fetching packages: " . $conn->error;
    }
} else {
    // Initialize empty arrays if tables don't exist
    $assignments = [];
    $patients = [];
    $packages = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Package Management</title>
    <link rel="icon" href="logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../components/tm.css">
</head>
<body>
    <?php include_once 'index.php'; ?>

    <div class="container-fluid py-2 style="max-width: 50%;">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Patient Package Management</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $success_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Available Packages List -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">Available Medical Packages</h6>
                                <a href="package_list.php" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-list"></i> View All Packages
                                </a>
                            </div>
                            <div class="row row-cols-1 row-cols-md-3 g-4">
                                <?php 
                                // Fetch package list for display
                                $packageList = [];
                                $packageListQuery = "SELECT package_id, package_name, description, price, duration FROM medical_packages ORDER BY price";
                                $packageListResult = $conn->query($packageListQuery);
                                
                                if ($packageListResult && $packageListResult->num_rows > 0):
                                    while ($pkg = $packageListResult->fetch_assoc()): 
                                ?>
                                    <div class="col">
                                        <div class="card h-100 shadow-sm">
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($pkg['package_name']); ?></h5>
                                                <h6 class="card-subtitle mb-2 text-muted">₱<?php echo number_format($pkg['price'], 2); ?></h6>
                                                <p class="card-text small"><?php echo htmlspecialchars($pkg['description']); ?></p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="badge bg-primary"><?php echo $pkg['duration']; ?> days</span>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            onclick="selectPackage(<?php echo $pkg['package_id']; ?>)">
                                                        Select
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                    <div class="col-12">
                                        <div class="alert alert-info">
                                            No packages are currently available. Please create packages first.
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Assign Package Form -->
                        <form method="POST" class="mb-4">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Patient</label>
                                    <select name="patient_id" class="form-select" required>
                                        <option value="">Select Patient</option>
                                        <?php foreach ($patients as $patient): ?>
                                            <option value="<?php echo $patient['patient_id']; ?>">
                                                <?php 
                                                if (isset($patient['full_name'])) {
                                                    echo htmlspecialchars($patient['full_name']);
                                                } elseif (isset($patient['first_name']) && isset($patient['last_name'])) {
                                                    echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']);
                                                } elseif (isset($patient['first_name'])) {
                                                    echo htmlspecialchars($patient['first_name']);
                                                } elseif (isset($patient['last_name'])) {
                                                    echo htmlspecialchars($patient['last_name']);
                                                } else {
                                                    echo 'Patient ID: ' . $patient['patient_id'];
                                                }
                                                ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Package</label>
                                    <select name="package_id" class="form-select" required>
                                        <option value="">Select Package</option>
                                        <?php foreach ($packages as $package): ?>
                                            <option value="<?php echo $package['package_id']; ?>">
                                                <?php 
                                                if (isset($package['package_name'])) {
                                                    echo htmlspecialchars($package['package_name']);
                                                } else {
                                                    echo 'Package ID: ' . $package['package_id'];
                                                }
                                                ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" name="start_date" class="form-control" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">End Date</label>
                                    <input type="date" name="end_date" class="form-control" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select" required>
                                        <option value="active">Active</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="2"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Assign Package</button>
                        </form>

                        <!-- Assignments Table -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Patient Name</th>
                                        <th>Package</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($assignments) > 0): ?>
                                        <?php foreach ($assignments as $assignment): ?>
                                    <tr>
                                                <td>
                                                    <?php 
                                                    if (isset($assignment['patient_name'])) {
                                                        echo htmlspecialchars($assignment['patient_name']);
                                                    } else {
                                                        echo 'Patient ID: ' . $assignment['patient_id'];
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    if (isset($assignment['package_name'])) {
                                                        echo htmlspecialchars($assignment['package_name']);
                                                    } else {
                                                        echo 'Package ID: ' . $assignment['package_id'];
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($assignment['start_date'])); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($assignment['end_date'])); ?></td>
                                                <td>
                                                    <?php if ($assignment['status'] === 'active'): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php elseif ($assignment['status'] === 'completed'): ?>
                                                        <span class="badge bg-info">Completed</span>
                                                    <?php elseif ($assignment['status'] === 'cancelled'): ?>
                                                        <span class="badge bg-danger">Cancelled</span>
                                                    <?php endif; ?>
                                                </td>
                                        <td>
                                                    <button class="btn btn-sm btn-primary" onclick="updateStatus(<?php echo $assignment['patient_package_id']; ?>, '<?php echo $assignment['status']; ?>', '<?php echo $assignment['end_date']; ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </td>
                                    </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No package assignments found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Package Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="updateStatusForm">
                        <input type="hidden" name="patient_package_id" id="edit_patient_package_id">
                        <input type="hidden" name="update_status" value="1">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select" required>
                                <option value="active">Active</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" id="edit_end_date" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateStatus(id, status, endDate) {
            document.getElementById('edit_patient_package_id').value = id;
            document.getElementById('edit_status').value = status;
            document.getElementById('edit_end_date').value = endDate;
            
            new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
        }
        
        function selectPackage(packageId) {
            // Set the selected package in the dropdown
            document.querySelector('select[name="package_id"]').value = packageId;
            
            // Scroll to the form
            document.querySelector('form').scrollIntoView({ behavior: 'smooth' });
            
            // Highlight the form
            const form = document.querySelector('form');
            form.classList.add('border', 'border-primary');
            setTimeout(() => {
                form.classList.remove('border', 'border-primary');
            }, 2000);
        }

        document.addEventListener('DOMContentLoaded', function() {
            var sidebar = document.getElementById('sidebar');
            var sidebarToggle = document.getElementById('sidebarToggle');
            var backdrop = document.getElementById('sidebar-backdrop');
            function closeSidebar() {
                sidebar.classList.add('collapsed');
                sidebar.classList.remove('show-backdrop');
                backdrop.style.display = 'none';
            }
            function openSidebar() {
                sidebar.classList.remove('collapsed');
                sidebar.classList.add('show-backdrop');
                backdrop.style.display = 'block';
            }
            sidebarToggle.addEventListener('click', function() {
                if (sidebar.classList.contains('collapsed')) {
                    openSidebar();
                } else {
                    closeSidebar();
                }
            });
            backdrop.addEventListener('click', function() {
                closeSidebar();
            });
            // Sidebar starts open on page load
            sidebar.classList.remove('collapsed');
            sidebar.classList.remove('show-backdrop');
            backdrop.style.display = 'none';
        });
    </script>
</body>
</html> 