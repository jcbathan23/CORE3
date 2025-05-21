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

// First, disable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS=0");

// Create nurses table if not exists
$createNurses = "CREATE TABLE IF NOT EXISTS `nurses` (
    `nurse_id` int(11) NOT NULL AUTO_INCREMENT,
    `first_name` varchar(50) NOT NULL,
    `last_name` varchar(50) NOT NULL,
    `designation` varchar(50) NOT NULL,
    `status` enum('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`nurse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if (!$conn->query($createNurses)) {
    die("Error creating nurses table: " . $conn->error);
}

// Create the wards table if not exists
$createWards = "CREATE TABLE IF NOT EXISTS `wards` (
    `ward_id` int(11) NOT NULL AUTO_INCREMENT,
    `ward_name` varchar(100) NOT NULL,
    `capacity` int(11) NOT NULL,
    `floor` varchar(20) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`ward_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if (!$conn->query($createWards)) {
    die("Error creating wards table: " . $conn->error);
}

// Create the nurse_assignments table if not exists
$createNurseAssignments = "CREATE TABLE IF NOT EXISTS `nurse_assignments` (
    `assignment_id` int(11) NOT NULL AUTO_INCREMENT,
    `nurse_id` int(11) NOT NULL,
    `ward_id` int(11) NOT NULL,
    `shift` enum('morning','afternoon','night') NOT NULL,
    `start_date` date NOT NULL,
    `end_date` date NOT NULL,
    `status` enum('active','completed','cancelled') NOT NULL DEFAULT 'active',
    `notes` text DEFAULT NULL,
    `created_by` int(11) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`assignment_id`),
    KEY `nurse_id` (`nurse_id`),
    KEY `ward_id` (`ward_id`),
    CONSTRAINT `nurse_assignments_ibfk_1` FOREIGN KEY (`nurse_id`) REFERENCES `nurses` (`nurse_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `nurse_assignments_ibfk_2` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`ward_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if (!$conn->query($createNurseAssignments)) {
    die("Error creating nurse_assignments table: " . $conn->error);
}

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS=1");

// Check if nurses table is empty and add sample data if needed
$result = $conn->query("SELECT COUNT(*) as count FROM nurses");
$row = $result->fetch_assoc();
if ($row['count'] == 0) {
    $sampleNurses = [
        ['John', 'Smith', 'RN'],
        ['Jane', 'Doe', 'LPN'],
        ['Emily', 'Johnson', 'CNA']
    ];
    
    $stmt = $conn->prepare("INSERT INTO nurses (first_name, last_name, designation) VALUES (?, ?, ?)");
    foreach ($sampleNurses as $nurse) {
        $stmt->bind_param("sss", $nurse[0], $nurse[1], $nurse[2]);
        $stmt->execute();
    }
    $stmt->close();
}

// Check if wards table is empty and add sample data if needed
$result = $conn->query("SELECT COUNT(*) as count FROM wards");
$row = $result->fetch_assoc();
if ($row['count'] == 0) {
    $sampleWards = [
        ['General Ward', 30, '1st Floor'],
        ['ICU', 10, '2nd Floor'],
        ['Emergency', 15, '1st Floor'],
        ['Pediatric', 20, '3rd Floor']
    ];
    
    $stmt = $conn->prepare("INSERT INTO wards (ward_name, capacity, floor) VALUES (?, ?, ?)");
    foreach ($sampleWards as $ward) {
        $stmt->bind_param("sis", $ward[0], $ward[1], $ward[2]);
        $stmt->execute();
    }
    $stmt->close();
}

// Debug: Check if tables have data
echo "<!-- Debug Info:";
$result = $conn->query("SELECT COUNT(*) as count FROM nurses");
$row = $result->fetch_assoc();
echo "\nNurses count: " . $row['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM wards");
$row = $result->fetch_assoc();
echo "\nWards count: " . $row['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM nurse_assignments");
$row = $result->fetch_assoc();
echo "\nAssignments count: " . $row['count'];
echo "\n-->";

// Fetch all nurses for the dropdown
$nurses = [];
$query = "SELECT nurse_id, CONCAT(first_name, ' ', last_name, ' - ', designation) as nurse_name 
          FROM nurses 
          WHERE status = 'active' 
          ORDER BY first_name, last_name";
$result = $conn->query($query);
if (!$result) {
    die("Error fetching nurses: " . $conn->error);
}
while ($row = $result->fetch_assoc()) {
    $nurses[] = $row;
}

// Fetch all wards for the dropdown
$wards = [];
$query = "SELECT ward_id, ward_name FROM wards ORDER BY ward_name";
$result = $conn->query($query);
if (!$result) {
    die("Error fetching wards: " . $conn->error);
}
while ($row = $result->fetch_assoc()) {
    $wards[] = $row;
}

// Handle form submission for new assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['update_assignment'])) {
    $nurse_id = $_POST['nurse_id'] ?? '';
    $ward_id = $_POST['ward_id'] ?? '';
    $shift = $_POST['shift'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $status = $_POST['status'] ?? 'active';
    $notes = $_POST['notes'] ?? '';
    $created_by = $_SESSION['user_id'] ?? 1;

    if ($nurse_id && $ward_id && $shift && $start_date && $end_date) {
        $stmt = $conn->prepare("INSERT INTO nurse_assignments (nurse_id, ward_id, shift, start_date, end_date, status, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt) {
            $stmt->bind_param("iisssssi", $nurse_id, $ward_id, $shift, $start_date, $end_date, $status, $notes, $created_by);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Nurse assignment created successfully!";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $error_message = "Error creating assignment: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Handle assignment update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_assignment'])) {
    $assignment_id = $_POST['assignment_id'] ?? '';
    $ward_id = $_POST['ward_id'] ?? '';
    $shift = $_POST['shift'] ?? '';
    $status = $_POST['status'] ?? '';
    $notes = $_POST['notes'] ?? '';

    if ($assignment_id && $ward_id && $shift && $status) {
        $stmt = $conn->prepare("UPDATE nurse_assignments SET ward_id = ?, shift = ?, status = ?, notes = ? WHERE assignment_id = ?");
        
        if ($stmt) {
            $stmt->bind_param("isssi", $ward_id, $shift, $status, $notes, $assignment_id);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Assignment updated successfully!";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $error_message = "Error updating assignment: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error_message = "Error preparing statement: " . $conn->error;
        }
    } else {
        $error_message = "All required fields must be filled out.";
    }
}

// Handle assignment deletion
if (isset($_GET['delete'])) {
    $assignment_id = $_GET['delete'];
    
    $stmt = $conn->prepare("DELETE FROM nurse_assignments WHERE assignment_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $assignment_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Assignment deleted successfully!";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $error_message = "Error deleting assignment: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch all assignments with nurse and ward details
$assignments = [];
$query = "SELECT na.*, 
          CONCAT(n.first_name, ' ', n.last_name, ' - ', n.designation) as nurse_name,
          w.ward_name
          FROM nurse_assignments na
          LEFT JOIN nurses n ON na.nurse_id = n.nurse_id
          LEFT JOIN wards w ON na.ward_id = w.ward_id
          ORDER BY na.created_at DESC";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Assignment</title>
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
                            <h5 class="mb-0">Nurse Assignment</h5>
                        </div>
                        <div class="card-body">
                            <?php if (isset($_SESSION['success_message'])): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <?php 
                                    echo $_SESSION['success_message'];
                                    unset($_SESSION['success_message']);
                                    ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($error_message)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?php echo $error_message; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Assignment Form -->
                            <form method="POST" class="mb-4">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Nurse</label>
                                        <select name="nurse_id" class="form-select" required>
                                            <option value="">Select Nurse</option>
                                            <?php foreach ($nurses as $nurse): ?>
                                                <option value="<?php echo $nurse['nurse_id']; ?>">
                                                    <?php echo htmlspecialchars($nurse['nurse_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Ward/Unit</label>
                                        <select name="ward_id" class="form-select" required>
                                            <option value="">Select Ward</option>
                                            <?php foreach ($wards as $ward): ?>
                                                <option value="<?php echo $ward['ward_id']; ?>">
                                                    <?php echo htmlspecialchars($ward['ward_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Shift</label>
                                        <select name="shift" class="form-select" required>
                                            <option value="morning">Morning (6AM-2PM)</option>
                                            <option value="afternoon">Afternoon (2PM-10PM)</option>
                                            <option value="night">Night (10PM-6AM)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Start Date</label>
                                        <input type="date" name="start_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">End Date</label>
                                        <input type="date" name="end_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-select" required>
                                            <option value="active">Active</option>
                                            <option value="completed">Completed</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Notes</label>
                                        <textarea name="notes" class="form-control" rows="1"></textarea>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Assign Nurse</button>
                            </form>

                            <!-- Assignments Table -->
                            <div class="table-responsive mt-4">
                                <table class="table table-hover table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nurse Name</th>
                                            <th>Ward/Unit</th>
                                            <th>Shift</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Status</th>
                                            <th>Notes</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Fetch all assignments with nurse and ward details
                                        $query = "SELECT na.*, 
                                                CONCAT(n.first_name, ' ', n.last_name, ' - ', n.designation) as nurse_name,
                                                w.ward_name
                                                FROM nurse_assignments na
                                                LEFT JOIN nurses n ON na.nurse_id = n.nurse_id
                                                LEFT JOIN wards w ON na.ward_id = w.ward_id
                                                ORDER BY na.created_at DESC";
                                        $result = $conn->query($query);
                                        
                                        if ($result && $result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                echo "<tr>";
                                                echo "<td>" . htmlspecialchars($row['assignment_id']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['nurse_name']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['ward_name']) . "</td>";
                                                echo "<td>" . ucfirst(htmlspecialchars($row['shift'])) . "</td>";
                                                echo "<td>" . date('M d, Y', strtotime($row['start_date'])) . "</td>";
                                                echo "<td>" . date('M d, Y', strtotime($row['end_date'])) . "</td>";
                                                echo "<td>";
                                                $statusClass = '';
                                                switch($row['status']) {
                                                    case 'active':
                                                        $statusClass = 'bg-success';
                                                        break;
                                                    case 'completed':
                                                        $statusClass = 'bg-info';
                                                        break;
                                                    case 'cancelled':
                                                        $statusClass = 'bg-danger';
                                                        break;
                                                    default:
                                                        $statusClass = 'bg-secondary';
                                                }
                                                echo "<span class='badge {$statusClass}'>" . ucfirst($row['status']) . "</span>";
                                                echo "</td>";
                                                echo "<td>" . htmlspecialchars($row['notes'] ?? '') . "</td>";
                                                echo "<td>";
                                                echo "<button class='btn btn-sm btn-primary me-1' onclick='editAssignment(" . 
                                                    $row['assignment_id'] . ", " . 
                                                    $row['ward_id'] . ", \"" . 
                                                    $row['shift'] . "\", \"" . 
                                                    $row['status'] . "\", \"" . 
                                                    addslashes($row['notes'] ?? '') . "\")'>";
                                                echo "<i class='fas fa-edit'></i></button>";
                                                echo "<a href='?delete=" . $row['assignment_id'] . "' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure you want to delete this assignment?\")'>";
                                                echo "<i class='fas fa-trash'></i></a>";
                                                echo "</td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='9' class='text-center'>No assignments found</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Assignment Modal -->
    <div class="modal fade" id="editAssignmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Assignment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="editAssignmentForm">
                        <input type="hidden" name="assignment_id" id="edit_assignment_id">
                        <input type="hidden" name="update_assignment" value="1">
                        <div class="mb-3">
                            <label class="form-label">Ward/Unit</label>
                            <select name="ward_id" id="edit_ward_id" class="form-select" required>
                                <?php foreach ($wards as $ward): ?>
                                    <option value="<?php echo $ward['ward_id']; ?>">
                                        <?php echo htmlspecialchars($ward['ward_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Shift</label>
                            <select name="shift" id="edit_shift" class="form-select" required>
                                <option value="morning">Morning (6AM-2PM)</option>
                                <option value="afternoon">Afternoon (2PM-10PM)</option>
                                <option value="night">Night (10PM-6AM)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select" required>
                                <option value="active">Active</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" id="edit_notes" class="form-control" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Assignment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editAssignment(id, ward, shift, status, notes) {
            document.getElementById('edit_assignment_id').value = id;
            document.getElementById('edit_ward_id').value = ward;
            document.getElementById('edit_shift').value = shift;
            document.getElementById('edit_status').value = status;
            document.getElementById('edit_notes').value = notes;
            
            new bootstrap.Modal(document.getElementById('editAssignmentModal')).show();
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