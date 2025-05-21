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

// Check if patient_monitoring table exists, if not create it
$tableCheck = $conn->query("SHOW TABLES LIKE 'patient_monitoring'");
if ($tableCheck->num_rows == 0) {
    // Create patient_monitoring table with correct columns
    $createTable = "CREATE TABLE `patient_monitoring` (
        `monitoring_id` int(11) NOT NULL AUTO_INCREMENT,
        `patient_id` int(11) NOT NULL,
        `temperature` decimal(4,1) DEFAULT NULL,
        `blood_pressure` varchar(20) DEFAULT NULL,
        `pulse_rate` int(11) DEFAULT NULL,
        `condition` enum('stable','critical','improving','deteriorating') NOT NULL DEFAULT 'stable',
        `monitoring_time` datetime NOT NULL,
        `treatment_plan` text DEFAULT NULL,
        `created_by` int(11) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`monitoring_id`),
        KEY `patient_id` (`patient_id`),
        CONSTRAINT `patient_monitoring_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    if (!$conn->query($createTable)) {
        die("Error creating table: " . $conn->error);
    }
}

// Add treatment_plan column if missing
try {
    $conn->query("ALTER TABLE patient_monitoring ADD COLUMN treatment_plan TEXT NULL AFTER monitoring_time");
} catch (mysqli_sql_exception $e) {}

// Add notes column if missing
try {
    $conn->query("ALTER TABLE patient_monitoring DROP COLUMN notes");
} catch (mysqli_sql_exception $e) {}

// Process form submission for adding monitoring record
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['update_record'])) {
    $patient_id = $_POST['patient_id'];
    $temperature = $_POST['temperature'];
    $blood_pressure = $_POST['blood_pressure'];
    $pulse_rate = $_POST['pulse_rate'];
    $condition = $_POST['condition'];
    $monitoring_time = $_POST['monitoring_time'];
    $treatment_plan = isset($_POST['treatment_plan']) ? $_POST['treatment_plan'] : '';
    $created_by = $_SESSION['user_id'];
    
    // Prepare and execute SQL statement
    $stmt = $conn->prepare("INSERT INTO patient_monitoring (patient_id, temperature, blood_pressure, pulse_rate, `condition`, monitoring_time, treatment_plan, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt) {
        $stmt->bind_param("idsisssi", $patient_id, $temperature, $blood_pressure, $pulse_rate, $condition, $monitoring_time, $treatment_plan, $created_by);
        
        if ($stmt->execute()) {
            $success_message = "Monitoring record added successfully!";
        } else {
            $error_message = "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_message = "Error preparing statement: " . $conn->error;
    }
}

// Process form submission for updating monitoring record
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_record'])) {
    $monitoring_id = $_POST['monitoring_id'];
    $temperature = $_POST['temperature'];
    $blood_pressure = $_POST['blood_pressure'];
    $pulse_rate = $_POST['pulse_rate'];
    $condition = $_POST['condition'];
    $treatment_plan = isset($_POST['treatment_plan']) ? $_POST['treatment_plan'] : '';
    
    // Prepare and execute SQL statement
    $stmt = $conn->prepare("UPDATE patient_monitoring SET temperature = ?, blood_pressure = ?, pulse_rate = ?, `condition` = ?, treatment_plan = ? WHERE monitoring_id = ?");
    $stmt->bind_param("dsissi", $temperature, $blood_pressure, $pulse_rate, $condition, $treatment_plan, $monitoring_id);
    
    if ($stmt->execute()) {
        $success_message = "Monitoring record updated successfully!";
    } else {
        $error_message = "Error: " . $stmt->error;
    }
    
    $stmt->close();
}

// Process record deletion if requested
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $monitoring_id = $_GET['delete'];
    
    // Prepare and execute delete statement
    $stmt = $conn->prepare("DELETE FROM patient_monitoring WHERE monitoring_id = ?");
    $stmt->bind_param("i", $monitoring_id);
    
    if ($stmt->execute()) {
        $success_message = "Monitoring record deleted successfully!";
    } else {
        $error_message = "Error: " . $stmt->error;
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

// Fetch all monitoring records
$records = [];
$query = "
    SELECT pm.*, CONCAT(p.first_name, ' ', p.last_name) as patient_name 
    FROM patient_monitoring pm
    LEFT JOIN patients p ON pm.patient_id = p.patient_id
    ORDER BY pm.monitoring_time DESC
";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Monitoring</title>
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
                            <h5 class="mb-0">Patient Monitoring</h5>
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
                            
                            <!-- Add Monitoring Record Form -->
                            <form method="POST" class="mb-4">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Patient</label>
                                        <select name="patient_id" class="form-select" required>
                                            <option value="">Select Patient</option>
                                            <?php foreach ($patients as $patient): ?>
                                                <option value="<?php echo $patient['patient_id']; ?>">
                                                    <?php echo htmlspecialchars($patient['patient_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Vital Signs</label>
                                        <div class="input-group">
                                            <input type="number" name="temperature" class="form-control" placeholder="Temp (°C)" step="0.1" min="35.0" max="42.0" required>
                                            <input type="text" name="blood_pressure" class="form-control" placeholder="BP (e.g. 120/80)" pattern="[0-9]{2,3}/[0-9]{2,3}" required>
                                            <input type="number" name="pulse_rate" class="form-control" placeholder="Pulse" min="40" max="220" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Monitoring Time</label>
                                        <input type="datetime-local" name="monitoring_time" class="form-control" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Condition</label>
                                        <select name="condition" class="form-select" required>
                                            <option value="stable">Stable</option>
                                            <option value="critical">Critical</option>
                                            <option value="improving">Improving</option>
                                            <option value="deteriorating">Deteriorating</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Treatment Plan</label>
                                        <textarea name="treatment_plan" class="form-control" rows="1"></textarea>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Add Monitoring Record</button>
                            </form>

                            <!-- Monitoring Records Table -->
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Patient Name</th>
                                            <th>Temperature</th>
                                            <th>Blood Pressure</th>
                                            <th>Pulse Rate</th>
                                            <th>Condition</th>
                                            <th>Monitoring Time</th>
                                            <th>Treatment Plan</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($records) > 0): ?>
                                            <?php foreach ($records as $record): ?>
                                                <tr>
                                                    <td>
                                                        <?php 
                                                        echo isset($record['patient_name']) && $record['patient_name'] ? 
                                                            htmlspecialchars($record['patient_name']) : 'Unknown Patient'; 
                                                        ?>
                                                    </td>
                                                    <td><?php echo $record['temperature']; ?>°C</td>
                                                    <td><?php echo htmlspecialchars($record['blood_pressure']); ?></td>
                                                    <td><?php echo $record['pulse_rate']; ?></td>
                                                    <td>
                                                        <?php if ($record['condition'] === 'stable'): ?>
                                                            <span class="badge bg-success">Stable</span>
                                                        <?php elseif ($record['condition'] === 'critical'): ?>
                                                            <span class="badge bg-danger">Critical</span>
                                                        <?php elseif ($record['condition'] === 'improving'): ?>
                                                            <span class="badge bg-info">Improving</span>
                                                        <?php elseif ($record['condition'] === 'deteriorating'): ?>
                                                            <span class="badge bg-warning">Deteriorating</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo date('M d, Y H:i', strtotime($record['monitoring_time'])); ?></td>
                                                    <td><?php echo htmlspecialchars($record['treatment_plan']); ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-primary" 
                                                                onclick="editRecord(<?php echo $record['monitoring_id']; ?>, 
                                                                '<?php echo $record['temperature']; ?>', 
                                                                '<?php echo $record['blood_pressure']; ?>', 
                                                                <?php echo $record['pulse_rate']; ?>, 
                                                                '<?php echo $record['condition']; ?>', 
                                                                '<?php echo addslashes($record['treatment_plan'] ?? ''); ?>')">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <a href="?delete=<?php echo $record['monitoring_id']; ?>" 
                                                           class="btn btn-sm btn-danger"
                                                           onclick="return confirm('Are you sure you want to delete this record?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center">No monitoring records found</td>
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

    <!-- Edit Record Modal -->
    <div class="modal fade" id="editRecordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Monitoring Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="editRecordForm">
                        <input type="hidden" name="monitoring_id" id="edit_monitoring_id">
                        <input type="hidden" name="update_record" value="1">
                        <div class="mb-3">
                            <label class="form-label">Vital Signs</label>
                            <div class="input-group">
                                <input type="number" name="temperature" id="edit_temperature" class="form-control" placeholder="Temp (°C)" step="0.1" min="35.0" max="42.0" required>
                                <input type="text" name="blood_pressure" id="edit_blood_pressure" class="form-control" placeholder="BP (e.g. 120/80)" pattern="[0-9]{2,3}/[0-9]{2,3}" required>
                                <input type="number" name="pulse_rate" id="edit_pulse_rate" class="form-control" placeholder="Pulse" min="40" max="220" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Condition</label>
                            <select name="condition" id="edit_condition" class="form-select" required>
                                <option value="stable">Stable</option>
                                <option value="critical">Critical</option>
                                <option value="improving">Improving</option>
                                <option value="deteriorating">Deteriorating</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Treatment Plan</label>
                            <textarea name="treatment_plan" id="edit_treatment_plan" class="form-control" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Record</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editRecord(id, temperature, bp, pulse, condition, notes) {
            document.getElementById('edit_monitoring_id').value = id;
            document.getElementById('edit_temperature').value = temperature;
            document.getElementById('edit_blood_pressure').value = bp;
            document.getElementById('edit_pulse_rate').value = pulse;
            document.getElementById('edit_condition').value = condition;
            document.getElementById('edit_treatment_plan').value = notes;
            
            new bootstrap.Modal(document.getElementById('editRecordModal')).show();
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