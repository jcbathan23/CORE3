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

// Check if package_payments table exists, if not create it
$tableCheck = $conn->query("SHOW TABLES LIKE 'package_payments'");
if ($tableCheck->num_rows == 0) {
    $createTable = "CREATE TABLE `package_payments` (
      `payment_id` int(11) NOT NULL AUTO_INCREMENT,
      `patient_package_id` int(11) NOT NULL,
      `amount` decimal(10,2) NOT NULL,
      `payment_date` date NOT NULL,
      `payment_method` enum('cash','credit_card','debit_card','bank_transfer') NOT NULL,
      `notes` text DEFAULT NULL,
      `created_by` int(11) NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`payment_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    $conn->query($createTable);
}

// Process form submission for adding payment
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $patient_package_id = $_POST['patient_package_id'];
    $amount = $_POST['amount'];
    $payment_date = $_POST['payment_date'];
    $payment_method = $_POST['payment_method'];
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    $created_by = $_SESSION['user_id'];
    
    // Prepare and execute SQL statement
    $stmt = $conn->prepare("INSERT INTO package_payments (patient_package_id, amount, payment_date, payment_method, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("idsssi", $patient_package_id, $amount, $payment_date, $payment_method, $notes, $created_by);
    
    if ($stmt->execute()) {
        $success_message = "Payment added successfully!";
    } else {
        $error_message = "Error: " . $stmt->error;
    }
    
    $stmt->close();
}

// Fetch all patient packages for dropdown
$patient_packages = [];
$query = "
    SELECT pp.patient_package_id, CONCAT(p.first_name, ' ', p.last_name) as patient_name, mp.package_name 
    FROM patient_packages pp
    LEFT JOIN patients p ON pp.patient_id = p.patient_id
    LEFT JOIN medical_packages mp ON pp.package_id = mp.package_id
    WHERE pp.status = 'active'
    ORDER BY p.first_name, p.last_name
";

$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $patient_packages[] = $row;
    }
} else {
    $error_message = "Error fetching patient packages: " . $conn->error;
}

// Fetch all payments
$payments = [];
$query = "
    SELECT pp.*, CONCAT(p.first_name, ' ', p.last_name) as patient_name, mp.package_name, pt.start_date, pt.end_date
    FROM package_payments pp
    LEFT JOIN patient_packages pt ON pp.patient_package_id = pt.patient_package_id
    LEFT JOIN patients p ON pt.patient_id = p.patient_id
    LEFT JOIN medical_packages mp ON pt.package_id = mp.package_id
    ORDER BY pp.payment_date DESC
";

$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }
} else {
    $error_message = "Error fetching payments: " . $conn->error;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Package Billing</title>
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
                        <h5 class="mb-0">Package Billing</h5>
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
                                                
                                                echo $patientName . ' - ' . $packageName;
                                                ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Amount</label>
                                    <input type="number" name="amount" class="form-control" step="0.01" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Payment Date</label>
                                    <input type="date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Payment Method</label>
                                    <select name="payment_method" class="form-select" required>
                                        <option value="cash">Cash</option>
                                        <option value="credit_card">Credit Card</option>
                                        <option value="debit_card">Debit Card</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="1"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Payment</button>
                        </form>

                        <!-- Payments Table -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Patient Name</th>
                                        <th>Package</th>
                                        <th>Amount</th>
                                        <th>Payment Date</th>
                                        <th>Payment Method</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($payments) > 0): ?>
                                        <?php foreach ($payments as $payment): ?>
                                            <tr>
                                                <td>
                                                    <?php 
                                                    echo isset($payment['patient_name']) && $payment['patient_name'] ? 
                                                        htmlspecialchars($payment['patient_name']) : 'Unknown Patient'; 
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    echo isset($payment['package_name']) && $payment['package_name'] ? 
                                                        htmlspecialchars($payment['package_name']) : 'Unknown Package'; 
                                                    ?>
                                                </td>
                                                <td>₱<?php echo number_format($payment['amount'], 2); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                                <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                                <td><?php echo htmlspecialchars($payment['notes']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No payments found</td>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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