<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: loginDefault.php");
    exit();
}
include_once('admin.php');
include 'config.php';

// Create tables if they don't exist
$createBedsTable = "CREATE TABLE IF NOT EXISTS `beds` (
    `bed_id` int(11) NOT NULL AUTO_INCREMENT,
    `bed_number` varchar(10) NOT NULL,
    `room_number` varchar(10) NOT NULL,
    `status` enum('Available','Occupied','Maintenance') NOT NULL DEFAULT 'Available',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`bed_id`),
    UNIQUE KEY `room_bed` (`room_number`,`bed_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

$createPatientAssignmentsTable = "CREATE TABLE IF NOT EXISTS `patient_assignments` (
    `assignment_id` int(11) NOT NULL AUTO_INCREMENT,
    `bed_id` int(11) NOT NULL,
    `first_name` varchar(50) NOT NULL,
    `middle_initial` char(1),
    `last_name` varchar(50) NOT NULL,
    `diagnosis` varchar(255),
    `patient_type` enum('Inpatient','Outpatient') NOT NULL,
    `assignment_date` timestamp NOT NULL DEFAULT current_timestamp(),
    `status` enum('Active','Discharged') NOT NULL DEFAULT 'Active',
    PRIMARY KEY (`assignment_id`),
    FOREIGN KEY (`bed_id`) REFERENCES `beds`(`bed_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if (!$conn->query($createBedsTable)) {
    echo "Error creating beds table: " . $conn->error;
    exit();
}

if (!$conn->query($createPatientAssignmentsTable)) {
    echo "Error creating patient assignments table: " . $conn->error;
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $error = null;
        
        switch ($_POST['action']) {
            case 'assign':
                $bed_id = $_POST['bed_id'];
                $first_name = trim($_POST['first_name']);
                $middle_initial = trim($_POST['middle_initial']);
                $last_name = trim($_POST['last_name']);
                $diagnosis = trim($_POST['diagnosis']);
                $patient_type = $_POST['patient_type'];

                // Validate input
                if (empty($first_name) || empty($last_name)) {
                    $error = "First name and last name are required.";
                    break;
                }

                // Start transaction
                $conn->begin_transaction();

                try {
                    // Check if bed is available
                    $check_sql = "SELECT status FROM beds WHERE bed_id = ? FOR UPDATE";
                    $check_stmt = $conn->prepare($check_sql);
                    $check_stmt->bind_param("i", $bed_id);
                    $check_stmt->execute();
                    $bed_result = $check_stmt->get_result()->fetch_assoc();

                    if ($bed_result['status'] !== 'Available') {
                        throw new Exception("Selected bed is not available.");
                    }

                    // Update bed status
                    $update_bed_sql = "UPDATE beds SET status = 'Occupied' WHERE bed_id = ?";
                    $update_bed_stmt = $conn->prepare($update_bed_sql);
                    $update_bed_stmt->bind_param("i", $bed_id);
                    $update_bed_stmt->execute();

                    // Create patient assignment
                    $assign_sql = "INSERT INTO patient_assignments (bed_id, first_name, middle_initial, last_name, diagnosis, patient_type) 
                                 VALUES (?, ?, ?, ?, ?, ?)";
                    $assign_stmt = $conn->prepare($assign_sql);
                    $assign_stmt->bind_param("isssss", $bed_id, $first_name, $middle_initial, $last_name, $diagnosis, $patient_type);
                    $assign_stmt->execute();

                    $conn->commit();
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = $e->getMessage();
                }
                break;

            case 'discharge':
                $assignment_id = $_POST['assignment_id'];
                $bed_id = $_POST['bed_id'];

                $conn->begin_transaction();

                try {
                    // Delete the assignment record
                    $delete_assignment_sql = "DELETE FROM patient_assignments WHERE assignment_id = ?";
                    $delete_assignment_stmt = $conn->prepare($delete_assignment_sql);
                    $delete_assignment_stmt->bind_param("i", $assignment_id);
                    $delete_assignment_stmt->execute();

                    // Update bed status
                    $update_bed_sql = "UPDATE beds SET status = 'Available' WHERE bed_id = ?";
                    $update_bed_stmt = $conn->prepare($update_bed_sql);
                    $update_bed_stmt->bind_param("i", $bed_id);
                    $update_bed_stmt->execute();

                    $conn->commit();
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = $e->getMessage();
                }
                break;

            case 'add_bed':
                $bed_number = trim($_POST['bed_number']);
                $room_number = trim($_POST['room_number']);
                $status = 'Available';
                if (empty($bed_number) || empty($room_number)) {
                    $error = "Bed number and room number are required.";
                    break;
                }
                $check_sql = "SELECT bed_id FROM beds WHERE room_number = ? AND bed_number = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("ss", $room_number, $bed_number);
                $check_stmt->execute();
                if ($check_stmt->get_result()->num_rows > 0) {
                    $error = "A bed with this number already exists in this room.";
                    break;
                }
                $sql = "INSERT INTO beds (bed_number, room_number, status) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sss", $bed_number, $room_number, $status);
                if (!$stmt->execute()) {
                    $error = "Error adding bed: " . $stmt->error;
                }
                break;
        }

        // If there was an error, display it
        if ($error) {
            echo "<div class='alert alert-danger'>$error</div>";
        } else {
            // Use JavaScript redirect instead of PHP header
            echo "<script>window.location.href = '" . $_SERVER['PHP_SELF'] . "';</script>";
            exit();
        }
    }
}

// Fetch all beds with their current assignments
$sql = "SELECT b.*, 
        pa.assignment_id,
        pa.first_name,
        pa.middle_initial,
        pa.last_name,
        pa.diagnosis,
        pa.patient_type,
        pa.assignment_date
        FROM beds b
        LEFT JOIN (
            SELECT * FROM patient_assignments 
            WHERE status = 'Active'
        ) pa ON b.bed_id = pa.bed_id
        ORDER BY b.room_number, b.bed_number";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bed & Linen Management</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/fontawesome/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Bed Allocation</h2>
        <button class="btn btn-success mb-3" onclick="showAddBedModal()">Add Bed & Room</button>
        
        <!-- Beds and Assignments Table -->
        <div class="card">
            <div class="card-header">
                <h4>Current Bed Allocations</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Bed/Room</th>
                                <th>Status</th>
                                <th>Patient Information</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    Bed: <?php echo htmlspecialchars($row['bed_number']); ?><br>
                                    Room: <?php echo htmlspecialchars($row['room_number']); ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $row['status'] === 'Available' ? 'success' : 
                                            ($row['status'] === 'Occupied' ? 'danger' : 'warning'); 
                                    ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['status'] === 'Occupied'): ?>
                                        Name: <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['middle_initial'] . ' ' . $row['last_name']); ?><br>
                                        Type: <?php echo htmlspecialchars($row['patient_type']); ?><br>
                                        Diagnosis: <?php echo isset($row['diagnosis']) ? htmlspecialchars($row['diagnosis']) : 'Not specified'; ?><br>
                                        Since: <?php echo date('M d, Y H:i', strtotime($row['assignment_date'])); ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['status'] === 'Available'): ?>
                                        <button class="btn btn-sm btn-primary" onclick="assignBed(<?php echo $row['bed_id']; ?>)">
                                            Assign Patient
                                        </button>
                                    <?php else: ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="discharge">
                                            <input type="hidden" name="assignment_id" value="<?php echo $row['assignment_id']; ?>">
                                            <input type="hidden" name="bed_id" value="<?php echo $row['bed_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to discharge this patient?')">
                                                Discharge
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Patient Modal -->
    <div class="modal fade" id="assignPatientModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Patient to Bed</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="assignPatientForm" method="POST" action="">
                        <input type="hidden" name="action" value="assign">
                        <input type="hidden" name="bed_id" id="assign_bed_id">
                        <div class="mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="middle_initial" class="form-label">Middle Initial</label>
                            <input type="text" class="form-control" id="middle_initial" name="middle_initial" maxlength="1">
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="diagnosis" class="form-label">Diagnosis</label>
                            <input type="text" class="form-control" id="diagnosis" name="diagnosis" required>
                        </div>
                        <div class="mb-3">
                            <label for="patient_type" class="form-label">Patient Type</label>
                            <select class="form-select" id="patient_type" name="patient_type" required>
                                <option value="Inpatient">Inpatient</option>
                                <option value="Outpatient">Outpatient</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Assign Patient</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Bed Modal -->
    <div class="modal fade" id="addBedModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Bed & Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addBedForm" method="POST" action="">
                        <input type="hidden" name="action" value="add_bed">
                        <div class="mb-3">
                            <label for="bed_number" class="form-label">Bed Number</label>
                            <input type="text" class="form-control" id="bed_number" name="bed_number" required>
                        </div>
                        <div class="mb-3">
                            <label for="room_number" class="form-label">Room Number</label>
                            <input type="text" class="form-control" id="room_number" name="room_number" required>
                        </div>
                        <button type="submit" class="btn btn-success">Add Bed</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        function assignBed(bedId) {
            document.getElementById('assign_bed_id').value = bedId;
            new bootstrap.Modal(document.getElementById('assignPatientModal')).show();
        }

        function showAddBedModal() {
            new bootstrap.Modal(document.getElementById('addBedModal')).show();
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
