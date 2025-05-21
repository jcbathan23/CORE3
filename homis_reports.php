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

// Define variables for filter persistence
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$report_type = $_GET['report_type'] ?? 'financial';
$department = $_GET['department'] ?? 'all';

// Initialize variables
$total_revenue = 0;
$total_expenses = 0;
$net_income = 0;
$pending_payments = 0;

// Fetch financial data based on filters
$financial_query = "SELECT 
    COALESCE(SUM(CASE WHEN status = 'Paid' THEN amount ELSE 0 END), 0) as total_revenue,
    COALESCE(SUM(CASE WHEN status = 'Pending' THEN amount ELSE 0 END), 0) as pending_payments,
    COALESCE(SUM(CASE WHEN status = 'Paid' AND service_type = ? THEN amount ELSE 0 END), 0) as service_revenue
FROM homis_bills 
WHERE created_at BETWEEN ? AND ?";

$stmt = $conn->prepare($financial_query);
$stmt->bind_param("sss", $department, $start_date, $end_date);
$stmt->execute();
$financial_result = $stmt->get_result();

if ($row = $financial_result->fetch_assoc()) {
    $total_revenue = $row['total_revenue'];
    $pending_payments = $row['pending_payments'];
}

// Fetch expenses from inventory
$expenses_query = "SELECT 
    COALESCE(SUM(CASE WHEN transaction_type = 'Out' THEN t.quantity * i.price ELSE 0 END), 0) as total_expenses
FROM homis_inventory_transactions t
JOIN homis_inventory i ON t.item_id = i.item_id
WHERE transaction_date BETWEEN ? AND ?";

$stmt = $conn->prepare($expenses_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$expenses_result = $stmt->get_result();

if ($row = $expenses_result->fetch_assoc()) {
    $total_expenses = $row['total_expenses'];
}

$net_income = $total_revenue - $total_expenses;

// Fetch service-wise breakdown
$service_query = "SELECT 
    service_type,
    COALESCE(SUM(CASE WHEN status = 'Paid' THEN amount ELSE 0 END), 0) as revenue,
    COUNT(*) as total_cases
FROM homis_bills 
WHERE created_at BETWEEN ? AND ?
GROUP BY service_type";

$stmt = $conn->prepare($service_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$service_result = $stmt->get_result();

$service_data = [];
while ($row = $service_result->fetch_assoc()) {
    $service_data[] = $row;
}

// Fetch service statistics
$service_stats_query = "SELECT 
    service_type,
    COUNT(*) as total_cases,
    COALESCE(AVG(amount), 0) as average_cost,
    COALESCE(SUM(CASE WHEN status = 'Paid' THEN amount ELSE 0 END), 0) as total_revenue,
    COALESCE(SUM(CASE WHEN status = 'Paid' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 0) as success_rate
FROM homis_bills 
WHERE created_at BETWEEN ? AND ?
GROUP BY service_type";

$stmt = $conn->prepare($service_stats_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$service_stats_result = $stmt->get_result();

$service_stats = [];
while ($row = $service_stats_result->fetch_assoc()) {
    $service_stats[] = $row;
}

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOMIS Reports</title>
    <link rel="icon" href="logo.png">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
    <link href='assets/vendor/boxicons/css/boxicons.min.css' rel='stylesheet'>
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
                            <h5 class="mb-0">HOMIS Reports</h5>
                        </div>
                        <div class="card-body">
                            <!-- Report Filters -->
                            <form method="GET" action="" class="mb-4">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Start Date</label>
                                        <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($start_date); ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">End Date</label>
                                        <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($end_date); ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Report Type</label>
                                        <select name="report_type" class="form-select">
                                            <option value="financial" <?php echo ($report_type === 'financial') ? 'selected' : ''; ?>>Financial Report</option>
                                            <option value="inventory" <?php echo ($report_type === 'inventory') ? 'selected' : ''; ?>>Inventory Report</option>
                                            <option value="patient" <?php echo ($report_type === 'patient') ? 'selected' : ''; ?>>Patient Report</option>
                                            <option value="service" <?php echo ($report_type === 'service') ? 'selected' : ''; ?>>Service Report</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Service Type</label>
                                        <select name="department" class="form-select">
                                            <option value="all" <?php echo ($department === 'all') ? 'selected' : ''; ?>>All Services</option>
                                            <option value="consultation" <?php echo ($department === 'consultation') ? 'selected' : ''; ?>>Consultation</option>
                                            <option value="procedure" <?php echo ($department === 'procedure') ? 'selected' : ''; ?>>Procedure</option>
                                            <option value="medication" <?php echo ($department === 'medication') ? 'selected' : ''; ?>>Medication</option>
                                            <option value="room" <?php echo ($department === 'room') ? 'selected' : ''; ?>>Room</option>
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Generate Report</button>
                            </form>

                            <!-- Financial Overview -->
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="card bg-primary text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Total Revenue</h6>
                                            <h3 class="mb-0">₱<?php echo number_format($total_revenue, 2); ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-success text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Total Expenses</h6>
                                            <h3 class="mb-0">₱<?php echo number_format($total_expenses, 2); ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-info text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Net Income</h6>
                                            <h3 class="mb-0">₱<?php echo number_format($net_income, 2); ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-warning text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Pending Payments</h6>
                                            <h3 class="mb-0">₱<?php echo number_format($pending_payments, 2); ?></h3>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Financial Report Table -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Service Summary</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Service Type</th>
                                                    <th>Revenue</th>
                                                    <th>Total Cases</th>
                                                    <th>% of Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($service_data as $service): ?>
                                                <tr>
                                                    <td><?php echo ucfirst(htmlspecialchars($service['service_type'])); ?></td>
                                                    <td>₱<?php echo number_format($service['revenue'], 2); ?></td>
                                                    <td><?php echo number_format($service['total_cases']); ?></td>
                                                    <td><?php echo $total_revenue > 0 ? number_format(($service['revenue'] / $total_revenue) * 100, 1) : '0.0'; ?>%</td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Service Statistics -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Service Statistics</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Service Type</th>
                                                    <th>Total Cases</th>
                                                    <th>Average Cost</th>
                                                    <th>Total Revenue</th>
                                                    <th>Success Rate</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($service_stats as $stat): ?>
                                                <tr>
                                                    <td><?php echo ucfirst(htmlspecialchars($stat['service_type'])); ?></td>
                                                    <td><?php echo number_format($stat['total_cases']); ?></td>
                                                    <td>₱<?php echo number_format($stat['average_cost'], 2); ?></td>
                                                    <td>₱<?php echo number_format($stat['total_revenue'], 2); ?></td>
                                                    <td><?php echo number_format($stat['success_rate'], 1); ?>%</td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Export Options -->
                            <div class="text-end">
                                <button class="btn btn-success me-2" onclick="exportToExcel()">
                                    <i class="fas fa-file-excel me-2"></i>Export to Excel
                                </button>
                                <button class="btn btn-danger" onclick="exportToPDF()">
                                    <i class="fas fa-file-pdf me-2"></i>Export to PDF
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        function getFilterValues() {
            const startDate = document.querySelector('input[name="start_date"]').value;
            const endDate = document.querySelector('input[name="end_date"]').value;
            const reportType = document.querySelector('select[name="report_type"]').value;
            const department = document.querySelector('select[name="department"]').value;
            return `start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}&report_type=${encodeURIComponent(reportType)}&department=${encodeURIComponent(department)}`;
        }

        function exportToExcel() {
            const filters = getFilterValues();
            window.location.href = `export_handler.php?format=excel&${filters}`;
        }

        function exportToPDF() {
            const filters = getFilterValues();
            window.location.href = `export_handler.php?format=pdf&${filters}`;
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