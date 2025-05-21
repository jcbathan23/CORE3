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

// Check if package_services table exists, if not create it
$tableCheck = $conn->query("SHOW TABLES LIKE 'package_services'");
if ($tableCheck->num_rows == 0) {
    $createTable = "CREATE TABLE `package_services` (
      `service_id` int(11) NOT NULL AUTO_INCREMENT,
      `patient_package_id` int(11) NOT NULL,
      `service_name` varchar(255) NOT NULL,
      `service_date` date NOT NULL,
      `status` enum('pending','completed','cancelled') NOT NULL DEFAULT 'pending',
      `notes` text DEFAULT NULL,
      `created_by` int(11) NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`service_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    $conn->query($createTable);
}

// Process form submission for adding service utilization
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['update_service'])) {
    $patient_package_id = $_POST['patient_package_id'];
    $service_name = $_POST['service_name'];
    $service_date = $_POST['service_date'];
    $status = $_POST['status'];
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    $created_by = $_SESSION['user_id'];
    
    // Prepare and execute SQL statement
    $stmt = $conn->prepare("INSERT INTO package_services (patient_package_id, service_name, service_date, status, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssi", $patient_package_id, $service_name, $service_date, $status, $notes, $created_by);
    
    if ($stmt->execute()) {
        $success_message = "Service utilization added successfully!";
    } else {
        $error_message = "Error: " . $stmt->error;
    }
    
    $stmt->close();
}

// Process form submission for updating service status
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_service'])) {
    $service_id = $_POST['service_id'];
    $status = $_POST['status'];
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    
    // Prepare and execute SQL statement
    $stmt = $conn->prepare("UPDATE package_services SET status = ?, notes = ? WHERE service_id = ?");
    $stmt->bind_param("ssi", $status, $notes, $service_id);
    
    if ($stmt->execute()) {
        $success_message = "Service status updated successfully!";
    } else {
        $error_message = "Error: " . $stmt->error;
    }
    
    $stmt->close();
}

// Fetch all active patient packages for dropdown
$patient_packages = [];
$query = "
    SELECT 
        pp.patient_package_id,
        CONCAT(p.first_name, ' ', p.last_name) as patient_name,
        mp.package_name,
        mp.services,
        pp.start_date,
        pp.end_date,
        pp.status
    FROM patient_packages pp
    INNER JOIN patients p ON pp.patient_id = p.patient_id
    INNER JOIN medical_packages mp ON pp.package_id = mp.package_id
    WHERE pp.status = 'active'
    ORDER BY p.first_name, p.last_name, pp.start_date DESC
";

$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $patient_packages[] = $row;
    }
} else {
    $error_message = "Error fetching patient packages: " . $conn->error;
}

// Fetch all services with related information
$services = [];
$query = "
    SELECT 
        ps.*,
        CONCAT(p.first_name, ' ', p.last_name) as patient_name,
        mp.package_name,
        pp.start_date as package_start_date,
        pp.end_date as package_end_date,
        pp.status as package_status
    FROM package_services ps
    INNER JOIN patient_packages pp ON ps.patient_package_id = pp.patient_package_id
    INNER JOIN patients p ON pp.patient_id = p.patient_id
    INNER JOIN medical_packages mp ON pp.package_id = mp.package_id
    ORDER BY ps.service_date DESC, ps.created_at DESC
";

$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
} else {
    $error_message = "Error fetching services: " . $conn->error;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Utilization Tracking</title>
    <link rel="icon" href="logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../components/tm.css">
</head>
<body>
    <?php include_once 'index.php'; ?>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Service Utilization Tracking</h5>
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

                        <!-- Add Service Utilization Form -->
                        <form method="POST" class="mb-4">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Patient Package</label>
                                    <select name="patient_package_id" class="form-select" required>
                                        <option value="">Select Patient Package</option>
                                        <?php foreach ($patient_packages as $package): ?>
                                            <option value="<?php echo $package['patient_package_id']; ?>">
                                                <?php 
                                                $patientName = isset($package['patient_name']) && $package['patient_name'] ? 
                                                    htmlspecialchars($package['patient_name']) : 'Unknown Patient';
                                                
                                                $packageName = isset($package['package_name']) && $package['package_name'] ? 
                                                    htmlspecialchars($package['package_name']) : 'Unknown Package';
                                                
                                                $startDate = date('M d, Y', strtotime($package['start_date']));
                                                $endDate = date('M d, Y', strtotime($package['end_date']));
                                                
                                                echo "$patientName - $packageName (Valid: $startDate to $endDate)";
                                                ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <!-- Add a helper text to show available services -->
                                    <div class="form-text mt-2" id="availableServices">
                                        Select a package to see available services
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Service Name</label>
                                    <input type="text" name="service_name" class="form-control" required list="servicesList">
                                    <datalist id="servicesList">
                                        <!-- Will be populated by JavaScript -->
                                    </datalist>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Service Date</label>
                                    <input type="date" name="service_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select" required>
                                        <option value="pending">Pending</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="2"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Service Utilization</button>
                        </form>

                        <!-- Services Table -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Package</th>
                                        <th>Service Name</th>
                                        <th>Service Date</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($services) > 0): ?>
                                        <?php foreach ($services as $service): ?>
                                            <tr>
                                                <td>
                                                    <?php 
                                                    echo isset($service['patient_name']) && $service['patient_name'] ? 
                                                        htmlspecialchars($service['patient_name']) : 'Unknown Patient'; 
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    echo isset($service['package_name']) && $service['package_name'] ? 
                                                        htmlspecialchars($service['package_name']) : 'Unknown Package'; 
                                                    ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($service['service_name']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($service['service_date'])); ?></td>
                                                <td>
                                                    <?php if ($service['status'] === 'pending'): ?>
                                                        <span class="badge bg-warning">Pending</span>
                                                    <?php elseif ($service['status'] === 'completed'): ?>
                                                        <span class="badge bg-success">Completed</span>
                                                    <?php elseif ($service['status'] === 'cancelled'): ?>
                                                        <span class="badge bg-danger">Cancelled</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($service['notes']); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" onclick="updateService(<?php echo $service['service_id']; ?>, '<?php echo $service['status']; ?>', '<?php echo addslashes($service['notes']); ?>')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No services found</td>
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

    <!-- Update Service Modal -->
    <div class="modal fade" id="updateServiceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Service Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="updateServiceForm">
                        <input type="hidden" name="service_id" id="edit_service_id">
                        <input type="hidden" name="update_service" value="1">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select" required>
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" id="edit_notes" class="form-control" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateService(id, status, notes) {
            document.getElementById('edit_service_id').value = id;
            document.getElementById('edit_status').value = status;
            document.getElementById('edit_notes').value = notes;
            
            new bootstrap.Modal(document.getElementById('updateServiceModal')).show();
        }

        // Store package services data
        const packageServices = <?php echo json_encode($patient_packages); ?>;
        
        // Function to update available services
        function updateAvailableServices() {
            const packageSelect = document.querySelector('select[name="patient_package_id"]');
            const availableServicesDiv = document.getElementById('availableServices');
            const servicesList = document.getElementById('servicesList');
            
            // Clear existing options
            servicesList.innerHTML = '';
            
            if (packageSelect.value) {
                const selectedPackage = packageServices.find(p => p.patient_package_id === packageSelect.value);
                if (selectedPackage && selectedPackage.services) {
                    const services = selectedPackage.services.split('\n').filter(s => s.trim());
                    
                    // Update helper text
                    availableServicesDiv.innerHTML = '<strong>Available Services:</strong><br>' + 
                        services.map(s => '- ' + s.trim()).join('<br>');
                    
                    // Add to datalist
                    services.forEach(service => {
                        const option = document.createElement('option');
                        option.value = service.trim();
                        servicesList.appendChild(option);
                    });
                }
            } else {
                availableServicesDiv.textContent = 'Select a package to see available services';
            }
        }
        
        // Add event listener to package select
        document.querySelector('select[name="patient_package_id"]').addEventListener('change', updateAvailableServices);

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