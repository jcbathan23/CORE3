<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: loginDefault.php");
    exit();
}

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

// Create pharmacy_prescriptions table if it doesn't exist
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

$conn->query($create_prescriptions);

// Create pharmacy_prescription_items table if it doesn't exist
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

$conn->query($create_prescription_items);

// Create pharmacy_sales table if it doesn't exist
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

$conn->query($create_sales);

// Create pharmacy_sale_items table if it doesn't exist
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

$conn->query($create_sale_items);

// Insert sample data if the prescriptions table is empty
$check_empty = $conn->query("SELECT COUNT(*) as count FROM pharmacy_prescriptions");
$row = $check_empty->fetch_assoc();

if ($row['count'] == 0) {
    $insert_sample = "INSERT INTO pharmacy_prescriptions (patient_name, doctor, prescription_date, status) 
                     VALUES 
                     ('John Doe', 'Dr. Smith', CURDATE(), 'pending'),
                     ('Jane Smith', 'Dr. Johnson', CURDATE(), 'pending')";
    $conn->query($insert_sample);
}

// Process form submission for filling a prescription
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['fill_prescription'])) {
    $prescription_id = $_POST['prescription_id'];
    $dispensed_by = $_POST['dispensed_by'];
    $dispensing_date = $_POST['dispensing_date'];
    $notes = $_POST['notes'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Update prescription status
        $stmt = $conn->prepare("UPDATE pharmacy_prescriptions SET status = 'filled', dispensed_by = ?, dispensing_date = ?, notes = CONCAT(IFNULL(notes, ''), ' ', ?) WHERE prescription_id = ?");
        $stmt->bind_param("sssi", $dispensed_by, $dispensing_date, $notes, $prescription_id);
        $stmt->execute();
        
        // Record the sale
        $stmt = $conn->prepare("INSERT INTO pharmacy_sales (prescription_id, patient_name, sale_date, total_amount, payment_method, created_by) 
                                SELECT ?, patient_name, ?, 
                                (SELECT SUM(pi.medicine_id * m.price) FROM pharmacy_prescription_items pi
                                JOIN pharmacy_inventory m ON pi.medicine_id = m.medicine_id
                                WHERE pi.prescription_id = ?), 'Cash', ?");
        $stmt->bind_param("issi", $prescription_id, $dispensing_date, $prescription_id, $dispensed_by);
        $stmt->execute();
        
        $sale_id = $conn->insert_id;
        
        // Add sale items
        $stmt = $conn->prepare("INSERT INTO pharmacy_sale_items (sale_id, medicine_id, medicine_name, quantity, unit_price, total_price)
                                SELECT ?, pi.medicine_id, pi.medicine_name, 1, m.price, m.price
                                FROM pharmacy_prescription_items pi
                                JOIN pharmacy_inventory m ON pi.medicine_id = m.medicine_id
                                WHERE pi.prescription_id = ?");
        $stmt->bind_param("ii", $sale_id, $prescription_id);
        $stmt->execute();
        
        // Update inventory
        $stmt = $conn->prepare("UPDATE pharmacy_inventory m
                                JOIN pharmacy_prescription_items pi ON m.medicine_id = pi.medicine_id
                                SET m.quantity = m.quantity - 1
                                WHERE pi.prescription_id = ?");
        $stmt->bind_param("i", $prescription_id);
        $stmt->execute();
        
        $conn->commit();
        $success_message = "Prescription filled successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error: " . $e->getMessage();
    }
}

// Process form submission for rejecting a prescription
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reject_prescription'])) {
    $prescription_id = $_POST['prescription_id'];
    $reason = $_POST['reason'];
    
    // Update prescription status
    $stmt = $conn->prepare("UPDATE pharmacy_prescriptions SET status = 'rejected', notes = CONCAT(IFNULL(notes, ''), ' Rejection reason: ', ?) WHERE prescription_id = ?");
    $stmt->bind_param("si", $reason, $prescription_id);
    
    if ($stmt->execute()) {
        $success_message = "Prescription rejected successfully!";
    } else {
        $error_message = "Error: " . $stmt->error;
    }
    
    $stmt->close();
}

// Add this after the existing POST handlers, before fetching statistics
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_patient'])) {
    $prescription_id = $_POST['edit_prescription_id'];
    $new_patient_name = $_POST['edit_patient_name'];
    
    $stmt = $conn->prepare("UPDATE pharmacy_prescriptions SET patient_name = ? WHERE prescription_id = ?");
    $stmt->bind_param("si", $new_patient_name, $prescription_id);
    
    if ($stmt->execute()) {
        $success_message = "Patient name updated successfully!";
    } else {
        $error_message = "Error updating patient name: " . $stmt->error;
    }
    
    $stmt->close();
}

// Fetch prescription statistics
$total = 0;
$filled = 0;
$pending = 0;
$rejected = 0;

$result = $conn->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'filled' THEN 1 ELSE 0 END) as filled,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
FROM pharmacy_prescriptions");

if ($result && $row = $result->fetch_assoc()) {
    $total = $row['total'];
    $filled = $row['filled'];
    $pending = $row['pending'];
    $rejected = $row['rejected'];
}

// Fetch all prescriptions
$prescriptions = [];
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

$sql = "SELECT * FROM pharmacy_prescriptions WHERE 1=1";

if (!empty($status_filter)) {
    $sql .= " AND status = '$status_filter'";
}

if (!empty($search_term)) {
    $sql .= " AND (patient_name LIKE '%$search_term%' OR doctor LIKE '%$search_term%')";
}

if (!empty($date_filter)) {
    $sql .= " AND DATE(prescription_date) = '$date_filter'";
}

$sql .= " ORDER BY prescription_date DESC";

$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $prescriptions[] = $row;
    }
}

// Create payments table if it doesn't exist
$create_payments = "CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id VARCHAR(50) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    middle_initial CHAR(1),
    last_name VARCHAR(100) NOT NULL,
    payment_status ENUM('pending', 'paid', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($create_payments);

// Add patient_id column if missing
try {
    $conn->query("ALTER TABLE payments ADD COLUMN patient_id VARCHAR(50) NOT NULL AFTER payment_id");
} catch (mysqli_sql_exception $e) {
    // Ignore error if column already exists
}

// Handle form submission for adding a payment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_payment'])) {
    $patient_id = trim($_POST['patient_id']);
    $first_name = trim($_POST['first_name']);
    $middle_initial = trim($_POST['middle_initial']);
    $last_name = trim($_POST['last_name']);
    $payment_status = $_POST['payment_status'];
    $diagnosis = trim($_POST['diagnosis']);
    $treatment_plan = trim($_POST['treatment_plan']);

    if ($patient_id && $first_name && $last_name && $payment_status) {
        $stmt = $conn->prepare("INSERT INTO payments (patient_id, first_name, middle_initial, last_name, diagnosis, treatment_plan, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $patient_id, $first_name, $middle_initial, $last_name, $diagnosis, $treatment_plan, $payment_status);
        if ($stmt->execute()) {
            $success_message = "Payment record added successfully!";
        } else {
            $error_message = "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_message = "Please fill in all required fields.";
    }
}

// Fetch all payments
$payments = [];
$result = $conn->query("SELECT * FROM payments ORDER BY created_at DESC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }
}

$conn->query("ALTER TABLE payments ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

// Add missing columns to payments table
try {
    $conn->query("ALTER TABLE payments ADD COLUMN first_name VARCHAR(100) NOT NULL AFTER patient_id");
} catch (mysqli_sql_exception $e) {}
try {
    $conn->query("ALTER TABLE payments ADD COLUMN middle_initial CHAR(1) AFTER first_name");
} catch (mysqli_sql_exception $e) {}
try {
    $conn->query("ALTER TABLE payments ADD COLUMN last_name VARCHAR(100) NOT NULL AFTER middle_initial");
} catch (mysqli_sql_exception $e) {}
try {
    $conn->query("ALTER TABLE payments ADD COLUMN payment_status ENUM('pending', 'paid', 'cancelled') NOT NULL DEFAULT 'pending' AFTER last_name");
} catch (mysqli_sql_exception $e) {}
try {
    $conn->query("ALTER TABLE payments ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER payment_status");
} catch (mysqli_sql_exception $e) {}
try {
    $conn->query("ALTER TABLE payments ADD COLUMN diagnosis VARCHAR(255) AFTER last_name");
} catch (mysqli_sql_exception $e) {}
try {
    $conn->query("ALTER TABLE payments ADD COLUMN treatment_plan VARCHAR(255) AFTER diagnosis");
} catch (mysqli_sql_exception $e) {}

// Handle form submission for editing a payment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_payment'])) {
    $edit_payment_id = $_POST['edit_payment_id'];
    $edit_patient_id = trim($_POST['edit_patient_id']);
    $edit_first_name = trim($_POST['edit_first_name']);
    $edit_middle_initial = trim($_POST['edit_middle_initial']);
    $edit_last_name = trim($_POST['edit_last_name']);
    $edit_payment_status = $_POST['edit_payment_status'];
    $edit_diagnosis = trim($_POST['edit_diagnosis']);
    $edit_treatment_plan = trim($_POST['edit_treatment_plan']);
    if ($edit_patient_id && $edit_first_name && $edit_last_name && $edit_payment_status) {
        $stmt = $conn->prepare("UPDATE payments SET patient_id=?, first_name=?, middle_initial=?, last_name=?, diagnosis=?, treatment_plan=?, payment_status=? WHERE payment_id=?");
        $stmt->bind_param("sssssssi", $edit_patient_id, $edit_first_name, $edit_middle_initial, $edit_last_name, $edit_diagnosis, $edit_treatment_plan, $edit_payment_status, $edit_payment_id);
        if ($stmt->execute()) {
            $success_message = "Payment record updated successfully!";
        } else {
            $error_message = "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_message = "Please fill in all required fields for editing.";
    }
}

// Handle delete action
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_payment'])) {
    $delete_payment_id = $_POST['delete_payment_id'];
    $stmt = $conn->prepare("DELETE FROM payments WHERE payment_id=?");
    $stmt->bind_param("i", $delete_payment_id);
    if ($stmt->execute()) {
        $success_message = "Payment record deleted successfully!";
    } else {
        $error_message = "Error: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments Management</title>
    <link rel="icon" href="logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../components/tm.css">
</head>
<body>
    <?php include_once 'index.php'; ?>
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Payments Management</h5>
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
                            <!-- Add Payment Form -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6>Add New Payment</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <div class="row">
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">Patient ID</label>
                                                <input type="text" name="patient_id" class="form-control" required>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">First Name</label>
                                                <input type="text" name="first_name" class="form-control" required>
                                            </div>
                                            <div class="col-md-1 mb-3">
                                                <label class="form-label">M.I.</label>
                                                <input type="text" name="middle_initial" class="form-control" maxlength="1">
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">Last Name</label>
                                                <input type="text" name="last_name" class="form-control" required>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">Payment Status</label>
                                                <select name="payment_status" class="form-select" required>
                                                    <option value="pending">Pending</option>
                                                    <option value="paid">Paid</option>
                                                    <option value="cancelled">Cancelled</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">Diagnosis</label>
                                                <input type="text" name="diagnosis" class="form-control">
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">Treatment Plan</label>
                                                <input type="text" name="treatment_plan" class="form-control">
                                            </div>
                                        </div>
                                        <button type="submit" name="add_payment" class="btn btn-primary">Add Payment</button>
                                    </form>
                                </div>
                            </div>
                            <!-- Payments Table -->
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Payment ID</th>
                                            <th>Patient ID</th>
                                            <th>First Name</th>
                                            <th>M.I.</th>
                                            <th>Last Name</th>
                                            <th>Diagnosis</th>
                                            <th>Treatment Plan</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td><?php echo $payment['payment_id']; ?></td>
                                            <td><?php echo htmlspecialchars($payment['patient_id']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['first_name']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['middle_initial']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['diagnosis']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['treatment_plan']); ?></td>
                                            <td>
                                                <?php if ($payment['payment_status'] == 'paid'): ?>
                                                    <span class="badge bg-success">Paid</span>
                                                <?php elseif ($payment['payment_status'] == 'pending'): ?>
                                                    <span class="badge bg-warning">Pending</span>
                                                <?php elseif ($payment['payment_status'] == 'cancelled'): ?>
                                                    <span class="badge bg-danger">Cancelled</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('Y-m-d', strtotime($payment['created_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-warning" onclick="editPayment(<?php echo htmlspecialchars(json_encode($payment)); ?>)"><i class="fas fa-edit"></i></button>
                                                <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this payment?');">
                                                    <input type="hidden" name="delete_payment_id" value="<?php echo $payment['payment_id']; ?>">
                                                    <button type="submit" name="delete_payment" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($payments)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No payments found</td>
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
    <!-- Edit Payment Modal -->
    <div class="modal fade" id="editPaymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="editPaymentForm">
                        <input type="hidden" name="edit_payment_id" id="edit_payment_id">
                        <div class="mb-3">
                            <label class="form-label">Patient ID</label>
                            <input type="text" name="edit_patient_id" id="edit_patient_id" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" name="edit_first_name" id="edit_first_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">M.I.</label>
                            <input type="text" name="edit_middle_initial" id="edit_middle_initial" class="form-control" maxlength="1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="edit_last_name" id="edit_last_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Status</label>
                            <select name="edit_payment_status" id="edit_payment_status" class="form-select" required>
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Diagnosis</label>
                            <input type="text" name="edit_diagnosis" id="edit_diagnosis" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Treatment Plan</label>
                            <input type="text" name="edit_treatment_plan" id="edit_treatment_plan" class="form-control">
                        </div>
                        <button type="submit" name="edit_payment" class="btn btn-primary">Update Payment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function editPayment(payment) {
        document.getElementById('edit_payment_id').value = payment.payment_id;
        document.getElementById('edit_patient_id').value = payment.patient_id;
        document.getElementById('edit_first_name').value = payment.first_name;
        document.getElementById('edit_middle_initial').value = payment.middle_initial;
        document.getElementById('edit_last_name').value = payment.last_name;
        document.getElementById('edit_diagnosis').value = payment.diagnosis;
        document.getElementById('edit_treatment_plan').value = payment.treatment_plan;
        document.getElementById('edit_payment_status').value = payment.payment_status;
        new bootstrap.Modal(document.getElementById('editPaymentModal')).show();
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
<?php $conn->close(); ?> 