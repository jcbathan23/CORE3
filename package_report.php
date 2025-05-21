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

// Get date range from request, default to current month
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Get statistics from the database
$stats = [];

// Total packages
$result = $conn->query("SELECT COUNT(*) as total FROM medical_packages");
$stats['total_packages'] = $result->fetch_assoc()['total'];

// Active packages (assigned to patients with active status)
$result = $conn->query("
    SELECT COUNT(DISTINCT pp.package_id) as active 
    FROM patient_packages pp 
    WHERE pp.status = 'active'
");
$stats['active_packages'] = $result->fetch_assoc()['active'];

// Total revenue (from package payments in the selected date range)
$stmt = $conn->prepare("
    SELECT SUM(amount) as revenue 
    FROM package_payments 
    WHERE payment_date BETWEEN ? AND ?
");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['total_revenue'] = $row['revenue'] ? $row['revenue'] : 0;

// Total services (from package_services table in the selected date range, or count of all packages * average services)
// Fallback to count of packages if package_services table doesn't exist
$tableCheck = $conn->query("SHOW TABLES LIKE 'package_services'");
if ($tableCheck->num_rows > 0) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total_services
        FROM package_services
        WHERE service_date BETWEEN ? AND ?
    ");
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['total_services'] = $result->fetch_assoc()['total_services'];
} else {
    // Estimate based on number of active packages
    $stats['total_services'] = $stats['active_packages'] * 5; // Assuming average of 5 services per package
}

// Get package statistics
$package_stats = [];
$query = "
    SELECT 
        mp.package_id,
        mp.package_name,
        COUNT(pp.patient_package_id) as total_assignments,
        SUM(CASE WHEN pp.status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN pp.status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN pp.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
        (SELECT SUM(pmt.amount) FROM package_payments pmt 
         JOIN patient_packages pp2 ON pmt.patient_package_id = pp2.patient_package_id 
         WHERE pp2.package_id = mp.package_id AND pmt.payment_date BETWEEN ? AND ?) as revenue
    FROM 
        medical_packages mp
    LEFT JOIN 
        patient_packages pp ON mp.package_id = pp.package_id
    GROUP BY 
        mp.package_id
    ORDER BY 
        total_assignments DESC, mp.package_name
";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $package_stats[] = $row;
}

// Get payment statistics
$payment_stats = [];
$query = "
    SELECT 
        DATE_FORMAT(payment_date, '%Y-%m') as month,
        payment_method,
        SUM(amount) as total_amount,
        COUNT(*) as num_payments
    FROM 
        package_payments
    WHERE 
        payment_date BETWEEN ? AND ?
    GROUP BY 
        DATE_FORMAT(payment_date, '%Y-%m'), payment_method
    ORDER BY 
        month, payment_method
";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $payment_stats[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Package Reports</title>
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
                        <h5 class="mb-0">Package Reports</h5>
                    </div>
                    <div class="card-body">
                        <!-- Date Range Filter -->
                        <form method="GET" class="mb-4">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">End Date</label>
                                    <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                                </div>
                                <div class="col-md-4 mb-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="export_report.php?format=excel&report_type=packages&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="btn btn-success ms-2">
                                        <i class="fas fa-file-excel"></i> Export
                                    </a>
                                </div>
                            </div>
                        </form>

                        <!-- Overall Statistics -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">Total Packages</h6>
                                        <h3 class="mb-0"><?php echo $stats['total_packages']; ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">Active Packages</h6>
                                        <h3 class="mb-0"><?php echo $stats['active_packages']; ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">Total Revenue</h6>
                                        <h3 class="mb-0">₱<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">Total Services</h6>
                                        <h3 class="mb-0"><?php echo $stats['total_services']; ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Package Statistics -->
                        <h6 class="mb-3">Package Statistics</h6>
                        <div class="table-responsive mb-4">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Package Name</th>
                                        <th>Total Assignments</th>
                                        <th>Active</th>
                                        <th>Completed</th>
                                        <th>Cancelled</th>
                                        <th>Total Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($package_stats) > 0): ?>
                                        <?php foreach ($package_stats as $package): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($package['package_name']); ?></td>
                                                <td><?php echo $package['total_assignments']; ?></td>
                                                <td><?php echo $package['active']; ?></td>
                                                <td><?php echo $package['completed']; ?></td>
                                                <td><?php echo $package['cancelled']; ?></td>
                                                <td>₱<?php echo number_format($package['revenue'] ? $package['revenue'] : 0, 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No package statistics available</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Payment Statistics -->
                        <h6 class="mb-3">Payment Statistics</h6>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th>Payment Method</th>
                                        <th>Total Amount</th>
                                        <th>Number of Payments</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($payment_stats) > 0): ?>
                                        <?php foreach ($payment_stats as $payment): ?>
                                            <tr>
                                                <td><?php echo date('F Y', strtotime($payment['month'] . '-01')); ?></td>
                                                <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                                <td>₱<?php echo number_format($payment['total_amount'], 2); ?></td>
                                                <td><?php echo $payment['num_payments']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No payment statistics available</td>
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