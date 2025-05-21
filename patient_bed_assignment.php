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

// Create patient_bed_assignments table if it doesn't exist
$createAssignmentsTable = "CREATE TABLE IF NOT EXISTS `patient_bed_assignments` (
        `assignment_id` int(11) NOT NULL AUTO_INCREMENT,
        `patient_id` int(11) NOT NULL,
        `bed_id` int(11) NOT NULL,
        `admission_date` datetime NOT NULL,
        `discharge_date` datetime DEFAULT NULL,
        `status` enum('Active','Discharged','Transferred') NOT NULL DEFAULT 'Active',
        `notes` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`assignment_id`),
        KEY `patient_id` (`patient_id`),
        KEY `bed_id` (`bed_id`),
        CONSTRAINT `patient_bed_assignments_ibfk_1` FOREIGN KEY (`bed_id`) REFERENCES `beds` (`bed_id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
if (!$conn->query($createAssignmentsTable)) {
        echo "Error creating patient_bed_assignments table: " . $conn->error;
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $error = null;
        
        switch ($_POST['action']) {
            case 'assign':
                $patient_id = trim($_POST['patient_id']);
                $bed_id = $_POST['bed_id'];
                $admission_date = $_POST['admission_date'];
                $status = 'Active';
                $source_table = $_POST['source_table'];

                // Validate input
                if (empty($patient_id) || !is_numeric($patient_id)) {
                    $error = "Valid patient selection is required.";
                    break;
                }

                if (empty($bed_id) || !is_numeric($bed_id)) {
                    $error = "Valid bed selection is required.";
                    break;
                }

                if (empty($admission_date)) {
                    $error = "Admission date is required.";
                    break;
                }

                // Check if patient already has an active assignment
                $check_sql = "SELECT COUNT(*) as count FROM patient_bed_assignments WHERE patient_id = ? AND status = 'Active'";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("i", $patient_id);
                $check_stmt->execute();
                if ($check_stmt->get_result()->fetch_assoc()['count'] > 0) {
                    $error = "Patient already has an active bed assignment.";
                    break;
                }

                // Check if bed is available
                $check_sql = "SELECT status FROM beds WHERE bed_id = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("i", $bed_id);
                $check_stmt->execute();
                $bed_status = $check_stmt->get_result()->fetch_assoc()['status'];
                if ($bed_status !== 'Available') {
                    $error = "Selected bed is not available.";
                    break;
                }

                // Start transaction
                $conn->begin_transaction();

                try {
                    // Get patient details based on source
                    if ($source_table === 'patient_assignments') {
                        $patient_sql = "SELECT first_name, middle_initial, last_name FROM patient_assignments WHERE assignment_id = ?";
                        $patient_stmt = $conn->prepare($patient_sql);
                        $patient_stmt->bind_param("i", $patient_id);
                        $patient_stmt->execute();
                        $patient_details = $patient_stmt->get_result()->fetch_assoc();
                        
                        // Insert into patients table first
                        $insert_patient_sql = "INSERT INTO patients (first_name, last_name) VALUES (?, ?)";
                        $insert_patient_stmt = $conn->prepare($insert_patient_sql);
                        $insert_patient_stmt->bind_param("ss", $patient_details['first_name'], $patient_details['last_name']);
                        $insert_patient_stmt->execute();
                        $patient_id = $insert_patient_stmt->insert_id;
                    }

                    // Insert assignment
                    $sql = "INSERT INTO patient_bed_assignments (patient_id, bed_id, admission_date, status) 
                            VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("iiss", $patient_id, $bed_id, $admission_date, $status);
                    if (!$stmt->execute()) {
                        throw new Exception("Error creating assignment: " . $stmt->error);
                    }

                    // Update bed status
                    $sql = "UPDATE beds SET status = 'Occupied' WHERE bed_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $bed_id);
                    if (!$stmt->execute()) {
                        throw new Exception("Error updating bed status: " . $stmt->error);
                    }

                    $conn->commit();
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = $e->getMessage();
                }
                break;

            case 'discharge':
                $assignment_id = $_POST['assignment_id'];
                $bed_id = $_POST['bed_id'];
                $discharge_date = $_POST['discharge_date'];

                // Validate input
                if (empty($discharge_date)) {
                    $error = "Discharge date is required.";
                    break;
                }

                // Start transaction
                $conn->begin_transaction();

                try {
                    // Delete the assignment record
                    $sql = "DELETE FROM patient_bed_assignments WHERE assignment_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $assignment_id);
                    if (!$stmt->execute()) {
                        throw new Exception("Error deleting assignment: " . $stmt->error);
                    }

                    // Update bed status
                    $sql = "UPDATE beds SET status = 'Available' WHERE bed_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $bed_id);
                    if (!$stmt->execute()) {
                        throw new Exception("Error updating bed status: " . $stmt->error);
                    }

                    $conn->commit();
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = $e->getMessage();
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

// Fetch available beds
$sql = "SELECT * FROM beds WHERE status = 'Available' ORDER BY room_number, bed_number";
$available_beds = $conn->query($sql);

// Fetch all patients for the dropdown (combining both tables)
$sql = "SELECT 
            p.patient_id, 
            CONCAT(p.first_name, ' ', p.last_name) as patient_name,
            'patients' as source_table
        FROM patients p
        UNION
        SELECT 
            pa.assignment_id as patient_id,
            CONCAT(pa.first_name, ' ', COALESCE(pa.middle_initial, ''), ' ', pa.last_name) as patient_name,
            'patient_assignments' as source_table
        FROM patient_assignments pa
        WHERE pa.status = 'Active'
        ORDER BY patient_name";
$patients = $conn->query($sql);

// Fetch current assignments with patient details
$sql = "SELECT pba.*, b.bed_number, b.room_number, b.bed_type, 
        CONCAT(p.first_name, ' ', p.last_name) as patient_name
        FROM patient_bed_assignments pba 
        JOIN beds b ON pba.bed_id = b.bed_id 
        JOIN patients p ON pba.patient_id = p.patient_id
        WHERE pba.status = 'Active' 
        ORDER BY pba.admission_date DESC";
$current_assignments = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Bed Assignment</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/fontawesome/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Patient Bed Assignment</h2>
        
        <!-- Assign Bed Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h4>Assign Bed to Patient</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="assign">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="patient_id" class="form-label">Select Patient</label>
                                <select class="form-select" id="patient_id" name="patient_id" required>
                                    <option value="">Choose a patient...</option>
                                    <?php while($patient = $patients->fetch_assoc()): ?>
                                        <option value="<?php echo $patient['patient_id']; ?>" data-source="<?php echo $patient['source_table']; ?>">
                                            <?php echo htmlspecialchars($patient['patient_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="bed_id" class="form-label">Select Bed</label>
                                <select class="form-select" id="bed_id" name="bed_id" required>
                                    <option value="">Choose a bed...</option>
                                    <?php while($bed = $available_beds->fetch_assoc()): ?>
                                        <option value="<?php echo $bed['bed_id']; ?>">
                                            Room <?php echo htmlspecialchars($bed['room_number']); ?> - 
                                            Bed <?php echo htmlspecialchars($bed['bed_number']); ?> 
                                            (<?php echo htmlspecialchars($bed['bed_type']); ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="admission_date" class="form-label">Admission Date</label>
                                <input type="datetime-local" class="form-control" id="admission_date" name="admission_date" required>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Assign Bed</button>
                </form>
            </div>
        </div>

        <!-- Current Assignments Table -->
        <div class="card">
            <div class="card-header">
                <h4>Current Assignments</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Patient ID</th>
                                <th>Patient Name</th>
                                <th>Room</th>
                                <th>Bed</th>
                                <th>Bed Type</th>
                                <th>Admission Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($assignment = $current_assignments->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $assignment['patient_id']; ?></td>
                                <td><?php echo htmlspecialchars($assignment['patient_name']); ?></td>
                                <td><?php echo htmlspecialchars($assignment['room_number']); ?></td>
                                <td><?php echo htmlspecialchars($assignment['bed_number']); ?></td>
                                <td><?php echo htmlspecialchars($assignment['bed_type']); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($assignment['admission_date'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" onclick="dischargePatient(<?php echo $assignment['assignment_id']; ?>, <?php echo $assignment['bed_id']; ?>)">
                                        <i class="fas fa-sign-out-alt"></i> Discharge
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Discharge Modal -->
    <div class="modal fade" id="dischargeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Discharge Patient</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="dischargeForm" method="POST" action="">
                        <input type="hidden" name="action" value="discharge">
                        <input type="hidden" name="assignment_id" id="discharge_assignment_id">
                        <input type="hidden" name="bed_id" id="discharge_bed_id">
                        <div class="mb-3">
                            <label for="discharge_date" class="form-label">Discharge Date</label>
                            <input type="datetime-local" class="form-control" id="discharge_date" name="discharge_date" required>
                        </div>
                        <button type="submit" class="btn btn-warning">Confirm Discharge</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        function dischargePatient(assignmentId, bedId) {
            document.getElementById('discharge_assignment_id').value = assignmentId;
            document.getElementById('discharge_bed_id').value = bedId;
            new bootstrap.Modal(document.getElementById('dischargeModal')).show();
        }

        // Add event listener for patient selection
        document.getElementById('patient_id').addEventListener('change', function() {
            var selectedOption = this.options[this.selectedIndex];
            var sourceTable = selectedOption.getAttribute('data-source');
            // Add a hidden input for the source table
            var existingInput = document.querySelector('input[name="source_table"]');
            if (existingInput) {
                existingInput.value = sourceTable;
            } else {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'source_table';
                input.value = sourceTable;
                this.parentNode.appendChild(input);
            }
        });

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