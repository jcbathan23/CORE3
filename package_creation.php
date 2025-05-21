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

// Check if medical_packages table exists, if not create it
$tableCheck = $conn->query("SHOW TABLES LIKE 'medical_packages'");
if ($tableCheck->num_rows == 0) {
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    $conn->query($createTable);
    
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
        ],
        [
            'name' => 'Maternity Package', 
            'desc' => 'Prenatal and postnatal care package for expectant mothers', 
            'price' => 12000.00, 
            'duration' => 270, 
            'services' => "OB-GYN consultations\nPrenatal vitamins\nUltrasounds\nLabor and delivery\nPostnatal care\nNewborn screening"
        ],
        [
            'name' => 'Senior Wellness', 
            'desc' => 'Comprehensive health package designed for seniors', 
            'price' => 6000.00, 
            'duration' => 90, 
            'services' => "Geriatric assessment\nBone density scan\nVision and hearing tests\nMemory evaluation\nPhysical therapy session\nNutritional guidance"
        ]
    ];
    
    $stmt = $conn->prepare("INSERT INTO medical_packages (package_name, description, price, duration, services) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($samplePackages as $package) {
        $stmt->bind_param("ssdis", $package['name'], $package['desc'], $package['price'], $package['duration'], $package['services']);
        $stmt->execute();
    }
    
    $stmt->close();
}

// Process form submission for adding new package
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['edit_package'])) {
    $package_name = $_POST['package_name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $duration = $_POST['duration'];
    $services = $_POST['services'];
    
    // Prepare and execute SQL statement
    $stmt = $conn->prepare("INSERT INTO medical_packages (package_name, description, price, duration, services) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdis", $package_name, $description, $price, $duration, $services);
    
    if ($stmt->execute()) {
        $success_message = "Package added successfully!";
    } else {
        $error_message = "Error: " . $stmt->error;
    }
    
    $stmt->close();
}

// Process form submission for updating package
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_package'])) {
    $package_id = $_POST['package_id'];
    $package_name = $_POST['package_name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $duration = $_POST['duration'];
    $services = $_POST['services'];
    
    // Prepare and execute SQL statement
    $stmt = $conn->prepare("UPDATE medical_packages SET package_name = ?, description = ?, price = ?, duration = ?, services = ? WHERE package_id = ?");
    $stmt->bind_param("ssdisi", $package_name, $description, $price, $duration, $services, $package_id);
    
    if ($stmt->execute()) {
        $success_message = "Package updated successfully!";
    } else {
        $error_message = "Error: " . $stmt->error;
    }
    
    $stmt->close();
}

// Process package deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $package_id = $_GET['delete'];
    
    // First check if the package is assigned to any patient
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM patient_packages WHERE package_id = ?");
    $checkStmt->bind_param("i", $package_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        $error_message = "Cannot delete package because it is assigned to patients.";
    } else {
        // Delete the package
        $deleteStmt = $conn->prepare("DELETE FROM medical_packages WHERE package_id = ?");
        $deleteStmt->bind_param("i", $package_id);
        
        if ($deleteStmt->execute()) {
            $success_message = "Package deleted successfully!";
        } else {
            $error_message = "Error: " . $deleteStmt->error;
        }
        
        $deleteStmt->close();
    }
    
    $checkStmt->close();
}

// Fetch all packages
$packages = [];
$stmt = $conn->prepare("SELECT * FROM medical_packages ORDER BY package_name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $packages[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Package Creation</title>
    <link rel="icon" href="logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../components/tm.css">
</head>
<body>
    <?php include_once 'index.php'; ?>

    <div class="container py-2" style="max-width: 80%; margin: 0 auto;">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Medical Package Creation</h5>
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

                        <!-- Create Package Form -->
                        <form method="POST" class="mb-4">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Package Name</label>
                                    <input type="text" name="package_name" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Price</label>
                                    <input type="number" name="price" class="form-control" step="0.01" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Duration (days)</label>
                                    <input type="number" name="duration" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Services</label>
                                    <textarea name="services" class="form-control" rows="3" required placeholder="List services included in this package"></textarea>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Create Package</button>
                        </form>

                        <!-- Packages Table -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Package Name</th>
                                        <th>Description</th>
                                        <th>Price</th>
                                        <th>Duration</th>
                                        <th>Services</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($packages) > 0): ?>
                                        <?php foreach ($packages as $package): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($package['package_name']); ?></td>
                                                <td><?php echo htmlspecialchars($package['description']); ?></td>
                                                <td>₱<?php echo number_format($package['price'], 2); ?></td>
                                                <td><?php echo $package['duration']; ?> days</td>
                                                <td><?php echo htmlspecialchars($package['services']); ?></td>
                                                <td>
                                                    <div style="display: flex; gap: 8px; align-items: center; justify-content: center;">
                                                        <button class="btn btn-sm btn-primary" onclick="editPackage(<?php echo $package['package_id']; ?>, '<?php echo addslashes($package['package_name']); ?>', '<?php echo addslashes($package['description']); ?>', <?php echo $package['price']; ?>, <?php echo $package['duration']; ?>, '<?php echo addslashes($package['services']); ?>')">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <a href="package_creation.php?delete=<?php echo $package['package_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this package?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No packages found</td>
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

    <!-- Edit Package Modal -->
    <div class="modal fade" id="editPackageModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Package</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="editPackageForm">
                        <input type="hidden" name="package_id" id="edit_package_id">
                        <input type="hidden" name="edit_package" value="1">
                        <div class="mb-3">
                            <label class="form-label">Package Name</label>
                            <input type="text" name="package_name" id="edit_package_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="edit_description" class="form-control" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" name="price" id="edit_price" class="form-control" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Duration (days)</label>
                            <input type="number" name="duration" id="edit_duration" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Services</label>
                            <textarea name="services" id="edit_services" class="form-control" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Package</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editPackage(id, name, description, price, duration, services) {
            document.getElementById('edit_package_id').value = id;
            document.getElementById('edit_package_name').value = name;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_price').value = price;
            document.getElementById('edit_duration').value = duration;
            document.getElementById('edit_services').value = services;
            
            new bootstrap.Modal(document.getElementById('editPackageModal')).show();
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