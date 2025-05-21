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

// Check if bed_allocations table exists, if not create it
$tableCheck = $conn->query("SHOW TABLES LIKE 'bed_allocations'");
if ($tableCheck->num_rows == 0) {
    $createTable = "CREATE TABLE `bed_allocations` (
      `allocation_id` int(11) NOT NULL AUTO_INCREMENT,
      `patient_id` int(11) NOT NULL,
      `room_number` varchar(20) NOT NULL,
      `bed_number` varchar(20) NOT NULL,
      `admission_date` date NOT NULL,
      `expected_discharge` date NOT NULL,
      `status` enum('occupied','reserved','maintenance') NOT NULL DEFAULT 'occupied',
      `treatment_plan` text DEFAULT NULL,
      `created_by` int(11) NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`allocation_id`),
      CONSTRAINT `patient_monitoring_ibfk_1` FOREIGN KEY (`patient_id`) 
      REFERENCES `patients` (`patient_id`) ON DELETE CASCADE ON UPDATE CASCADE,
      KEY `patient_id` (`patient_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    $conn->query($createTable);
}

// Add treatment_plan column if missing
try {
    $conn->query("ALTER TABLE bed_allocations ADD COLUMN treatment_plan TEXT NULL AFTER status");
} catch (mysqli_sql_exception $e) {}

// Add notes column if missing
try {
    $conn->query("ALTER TABLE bed_allocations DROP COLUMN notes");
} catch (mysqli_sql_exception $e) {}

// Add created_by column if missing
try {
    $conn->query("ALTER TABLE bed_allocations ADD COLUMN created_by INT(11) NOT NULL AFTER treatment_plan");
} catch (mysqli_sql_exception $e) {
    // Ignore error if column already exists
}

// Process form submission for adding bed allocation
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['update_allocation'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $room_number = $_POST['room_number'];
    $bed_number = $_POST['bed_number'];
    $admission_date = $_POST['admission_date'];
    $expected_discharge = $_POST['expected_discharge'];
    $status = $_POST['status'];
    $treatment_plan = isset($_POST['treatment_plan']) ? $_POST['treatment_plan'] : '';
    $created_by = $_SESSION['user_id'];
    
    // First, insert or get the patient
    $stmt = $conn->prepare("INSERT INTO patients (first_name, last_name, created_at) VALUES (?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("ss", $first_name, $last_name);
        
        if ($stmt->execute()) {
            $patient_id = $stmt->insert_id;
            $stmt->close();
            
            // Check if the bed is already allocated
            $checkQuery = "SELECT * FROM bed_allocations WHERE room_number = ? AND bed_number = ? AND status != 'maintenance'";
            $stmt = $conn->prepare($checkQuery);
            if ($stmt) {
                $stmt->bind_param("ss", $room_number, $bed_number);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $error_message = "This bed is already allocated to another patient.";
                } else {
                    // Prepare and execute SQL statement for insert
                    $stmt->close();
                    $stmt = $conn->prepare("INSERT INTO bed_allocations (patient_id, room_number, bed_number, admission_date, expected_discharge, status, treatment_plan, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt) {
                        $stmt->bind_param("issssssi", $patient_id, $room_number, $bed_number, $admission_date, $expected_discharge, $status, $treatment_plan, $created_by);
                        
                        if ($stmt->execute()) {
                            $success_message = "Bed allocated successfully!";
                        } else {
                            $error_message = "Error allocating bed: " . $stmt->error;
                        }
                    } else {
                        $error_message = "Error preparing allocation statement: " . $conn->error;
                    }
                }
            } else {
                $error_message = "Error checking bed availability: " . $conn->error;
            }
        } else {
            $error_message = "Error creating patient: " . $stmt->error;
        }
        if ($stmt) {
            $stmt->close();
        }
    } else {
        $error_message = "Error preparing patient statement: " . $conn->error;
    }
}

// Process form submission for updating bed allocation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_allocation'])) {
    $allocation_id = $_POST['allocation_id'];
    $room_number = $_POST['room_number'];
    $bed_number = $_POST['bed_number'];
    $expected_discharge = $_POST['expected_discharge'];
    $status = $_POST['status'];
    $treatment_plan = isset($_POST['treatment_plan']) ? $_POST['treatment_plan'] : '';
    
    // Check if the new bed is already allocated (if changing bed)
    $checkQuery = "SELECT * FROM bed_allocations WHERE room_number = ? AND bed_number = ? AND allocation_id != ? AND status != 'maintenance'";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ssi", $room_number, $bed_number, $allocation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $error_message = "This bed is already allocated to another patient.";
    } else {
        // Prepare and execute SQL statement for update
        $stmt = $conn->prepare("UPDATE bed_allocations SET room_number = ?, bed_number = ?, expected_discharge = ?, status = ?, treatment_plan = ? WHERE allocation_id = ?");
        $stmt->bind_param("ssssis", $room_number, $bed_number, $expected_discharge, $status, $treatment_plan, $allocation_id);
        
        if ($stmt->execute()) {
            $success_message = "Bed allocation updated successfully!";
        } else {
            $error_message = "Error: " . $stmt->error;
        }
    }
    
    $stmt->close();
}

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $allocation_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM bed_allocations WHERE allocation_id = ?");
    $stmt->bind_param("i", $allocation_id);
    if ($stmt->execute()) {
        $success_message = "Bed allocation deleted successfully!";
    } else {
        $error_message = "Error deleting allocation: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch all patients for the dropdown
$patients = [];
$query = "SELECT patient_id, CONCAT(first_name, ' ', last_name) as patient_name FROM patients ORDER BY first_name, last_name";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $patients[] = $row;
    }
}

// Fetch all allocations
$allocations = [];
$query = "
    SELECT ba.*, CONCAT(p.first_name, ' ', p.last_name) as patient_name 
    FROM bed_allocations ba
    LEFT JOIN patients p ON ba.patient_id = p.patient_id
    ORDER BY ba.created_at DESC
";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $allocations[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bed & Room Allocation</title>
    <link rel="icon" href="logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../components/tm.css">
    <style>
        .main-content {
            margin-left: 300px;
            transition: margin-left 0.3s;
            padding: 20px;
            width: calc(100% - 300px);
        }
        
        #sidebar.collapsed ~ .main-content {
            margin-left: 0;
            width: 100%;
        }

        .container-fluid {
            padding: 0;
            margin: 0;
            width: 100%;
        }

        .card {
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include_once 'index.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Bed & Room Allocation</h5>
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
                            
                            <!-- Allocation Form -->
                            <form method="POST" class="mb-4">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Patient First Name</label>
                                        <input type="text" name="first_name" class="form-control" required placeholder="Enter first name">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Patient Last Name</label>
                                        <input type="text" name="last_name" class="form-control" required placeholder="Enter last name">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Room Number</label>
                                        <select name="room_number" class="form-select" required>
                                            <option value="">Select Room</option>
                                            <option value="101">101 - Private Room</option>
                                            <option value="102">102 - Semi-Private Room</option>
                                            <option value="103">103 - Ward Room</option>
                                            <option value="201">201 - Private Room</option>
                                            <option value="202">202 - Semi-Private Room</option>
                                            <option value="203">203 - Ward Room</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Bed Number</label>
                                        <select name="bed_number" class="form-select" required>
                                            <option value="">Select Bed</option>
                                            <option value="1">Bed 1</option>
                                            <option value="2">Bed 2</option>
                                            <option value="3">Bed 3</option>
                                            <option value="4">Bed 4</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Admission Date</label>
                                        <input type="date" name="admission_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Expected Discharge</label>
                                        <input type="date" name="expected_discharge" class="form-control" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-select" required>
                                            <option value="occupied">Occupied</option>
                                            <option value="reserved">Reserved</option>
                                            <option value="maintenance">Under Maintenance</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Treatment Plan</label>
                                    <textarea name="treatment_plan" class="form-control" rows="2"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Allocate Bed</button>
                            </form>

                            <!-- Allocations Table -->
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Patient Name</th>
                                            <th>Room Number</th>
                                            <th>Bed Number</th>
                                            <th>Admission Date</th>
                                            <th>Expected Discharge</th>
                                            <th>Status</th>
                                            <th>Treatment Plan</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($allocations) > 0): ?>
                                            <?php foreach ($allocations as $allocation): ?>
                                                <tr>
                                                    <td>
                                                        <?php 
                                                        echo isset($allocation['patient_name']) && $allocation['patient_name'] ? 
                                                            htmlspecialchars($allocation['patient_name']) : 'Unknown Patient'; 
                                                        ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($allocation['room_number']); ?></td>
                                                    <td><?php echo htmlspecialchars($allocation['bed_number']); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($allocation['admission_date'])); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($allocation['expected_discharge'])); ?></td>
                                                    <td>
                                                        <?php
                                                            $status = strtolower(trim($allocation['status']));
                                                            if ($status === 'occupied') {
                                                                echo '<span class="badge bg-success">Occupied</span>';
                                                            } elseif ($status === 'reserved') {
                                                                echo '<span class="badge" style="background-color: #ffc107; color: #212529;">Reserved</span>';
                                                            } elseif ($status === 'maintenance') {
                                                                echo '<span class="badge" style="background-color: #6c757d; color: #fff;">Under Maintenance</span>';
                                                            } else {
                                                                echo '<span class="badge bg-secondary">' . htmlspecialchars($allocation['status']) . '</span>';
                                                            }
                                                        ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($allocation['treatment_plan']); ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-primary" 
                                                                onclick="editAllocation(<?php echo $allocation['allocation_id']; ?>, 
                                                                '<?php echo $allocation['room_number']; ?>', 
                                                                '<?php echo $allocation['bed_number']; ?>', 
                                                                '<?php echo $allocation['expected_discharge']; ?>', 
                                                                '<?php echo $allocation['status']; ?>', 
                                                                '<?php echo addslashes($allocation['treatment_plan'] ?? ''); ?>')">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <a href="?delete=<?php echo $allocation['allocation_id']; ?>" 
                                                           class="btn btn-sm btn-danger"
                                                           onclick="return confirm('Are you sure you want to delete this allocation?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center">No allocations found</td>
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
    </div>

    <!-- Edit Allocation Modal -->
    <div class="modal fade" id="editAllocationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Allocation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="editAllocationForm">
                        <input type="hidden" name="allocation_id" id="edit_allocation_id">
                        <input type="hidden" name="update_allocation" value="1">
                        <div class="mb-3">
                            <label class="form-label">Room Number</label>
                            <select name="room_number" id="edit_room_number" class="form-select" required>
                                <option value="101">101 - Private Room</option>
                                <option value="102">102 - Semi-Private Room</option>
                                <option value="103">103 - Ward Room</option>
                                <option value="201">201 - Private Room</option>
                                <option value="202">202 - Semi-Private Room</option>
                                <option value="203">203 - Ward Room</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bed Number</label>
                            <select name="bed_number" id="edit_bed_number" class="form-select" required>
                                <option value="1">Bed 1</option>
                                <option value="2">Bed 2</option>
                                <option value="3">Bed 3</option>
                                <option value="4">Bed 4</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Expected Discharge</label>
                            <input type="date" name="expected_discharge" id="edit_expected_discharge" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select" required>
                                <option value="occupied">Occupied</option>
                                <option value="reserved">Reserved</option>
                                <option value="maintenance">Under Maintenance</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Treatment Plan</label>
                            <textarea name="treatment_plan" id="edit_treatment_plan" class="form-control" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Allocation</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editAllocation(id, room, bed, discharge, status, notes) {
            document.getElementById('edit_allocation_id').value = id;
            document.getElementById('edit_room_number').value = room;
            document.getElementById('edit_bed_number').value = bed;
            document.getElementById('edit_expected_discharge').value = discharge;
            document.getElementById('edit_status').value = status;
            document.getElementById('edit_treatment_plan').value = notes;
            
            new bootstrap.Modal(document.getElementById('editAllocationModal')).show();
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