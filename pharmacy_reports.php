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

// Set default date range if not provided
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'sales';
$category = isset($_GET['category']) ? $_GET['category'] : 'all';

// Fetch sales statistics for the date range
$stats = [
    'total_sales' => 0,
    'total_prescriptions' => 0,
    'avg_sale' => 0,
    'items_sold' => 0
];

$sales_query = "SELECT 
    SUM(s.total_amount) as total_sales, 
    COUNT(DISTINCT s.sale_id) as total_sales_count,
    COUNT(DISTINCT s.prescription_id) as total_prescriptions,
    SUM(s.total_amount) / COUNT(DISTINCT s.sale_id) as avg_sale,
    COUNT(si.item_id) as items_sold
FROM pharmacy_sales s
LEFT JOIN pharmacy_sale_items si ON s.sale_id = si.sale_id
WHERE s.sale_date BETWEEN ? AND ?";

$stmt = $conn->prepare($sales_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $stats['total_sales'] = $row['total_sales'] ?: 0;
    $stats['total_prescriptions'] = $row['total_prescriptions'] ?: 0;
    $stats['avg_sale'] = $row['avg_sale'] ?: 0;
    $stats['items_sold'] = $row['items_sold'] ?: 0;
}

// Get sales by category
$sales_by_category = [];
$category_query = "SELECT 
    i.category,
    COUNT(si.item_id) as items_sold,
    SUM(si.total_price) as revenue,
    IFNULL((SUM(si.total_price) / NULLIF((SELECT SUM(total_price) FROM pharmacy_sale_items si2 
                            JOIN pharmacy_sales s2 ON si2.sale_id = s2.sale_id 
                            WHERE s2.sale_date BETWEEN ? AND ?), 0)) * 100, 0) as percentage
FROM pharmacy_inventory i
LEFT JOIN pharmacy_sale_items si ON i.medicine_id = si.medicine_id
LEFT JOIN pharmacy_sales s ON si.sale_id = s.sale_id AND s.sale_date BETWEEN ? AND ?
WHERE 1=1";

if ($category != 'all') {
    $category_query .= " AND i.category = ?";
}

$category_query .= " GROUP BY i.category";

try {
    if ($category != 'all') {
        $stmt = $conn->prepare($category_query);
        $stmt->bind_param("sssss", $start_date, $end_date, $start_date, $end_date, $category);
    } else {
        $stmt = $conn->prepare($category_query);
        $stmt->bind_param("ssss", $start_date, $end_date, $start_date, $end_date);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $sales_by_category[] = $row;
    }
} catch (Exception $e) {
    // Log the error but don't display it to the user
    error_log("Error in category query: " . $e->getMessage());
}

// Get top selling items
$top_selling_items = [];
$top_items_query = "SELECT 
    i.medicine_name,
    i.category,
    IFNULL(SUM(si.quantity), 0) as quantity_sold,
    IFNULL(SUM(si.total_price), 0) as revenue
FROM pharmacy_inventory i
LEFT JOIN pharmacy_sale_items si ON i.medicine_id = si.medicine_id
LEFT JOIN pharmacy_sales s ON si.sale_id = s.sale_id AND s.sale_date BETWEEN ? AND ?
WHERE 1=1";

if ($category != 'all') {
    $top_items_query .= " AND i.category = ?";
}

$top_items_query .= " GROUP BY i.medicine_id, i.medicine_name, i.category
                      ORDER BY quantity_sold DESC, revenue DESC
                      LIMIT 10";

try {
    if ($category != 'all') {
        $stmt = $conn->prepare($top_items_query);
        $stmt->bind_param("sss", $start_date, $end_date, $category);
    } else {
        $stmt = $conn->prepare($top_items_query);
        $stmt->bind_param("ss", $start_date, $end_date);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $top_selling_items[] = $row;
    }
} catch (Exception $e) {
    // Log the error but don't display it to the user
    error_log("Error in top items query: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Reports</title>
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
                            <h5 class="mb-0">Pharmacy Reports</h5>
                        </div>
                        <div class="card-body">
                            <!-- Report Filters -->
                            <form method="GET" class="mb-4">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Start Date</label>
                                        <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">End Date</label>
                                        <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Report Type</label>
                                        <select name="report_type" class="form-select">
                                            <option value="inventory" <?php if ($report_type == 'inventory') echo 'selected'; ?>>Inventory Report</option>
                                            <option value="sales" <?php if ($report_type == 'sales') echo 'selected'; ?>>Sales Report</option>
                                            <option value="prescriptions" <?php if ($report_type == 'prescriptions') echo 'selected'; ?>>Prescriptions Report</option>
                                            <option value="expiry" <?php if ($report_type == 'expiry') echo 'selected'; ?>>Expiry Report</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Category</label>
                                        <select name="category" class="form-select">
                                            <option value="all" <?php if ($category == 'all') echo 'selected'; ?>>All Categories</option>
                                            <option value="tablet" <?php if ($category == 'tablet') echo 'selected'; ?>>Tablets</option>
                                            <option value="capsule" <?php if ($category == 'capsule') echo 'selected'; ?>>Capsules</option>
                                            <option value="syrup" <?php if ($category == 'syrup') echo 'selected'; ?>>Syrups</option>
                                            <option value="injection" <?php if ($category == 'injection') echo 'selected'; ?>>Injections</option>
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Generate Report</button>
                            </form>

                            <!-- Sales Overview -->
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="card bg-primary text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Total Sales</h6>
                                            <h3 class="mb-0">₱<?php echo number_format($stats['total_sales'], 2); ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-success text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Total Prescriptions</h6>
                                            <h3 class="mb-0"><?php echo number_format($stats['total_prescriptions']); ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-info text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Average Sale</h6>
                                            <h3 class="mb-0">₱<?php echo number_format($stats['avg_sale'], 2); ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-warning text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Items Sold</h6>
                                            <h3 class="mb-0"><?php echo number_format($stats['items_sold']); ?></h3>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sales Report Table -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Sales Summary</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Category</th>
                                                    <th>Items Sold</th>
                                                    <th>Revenue</th>
                                                    <th>% of Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($sales_by_category)): ?>
                                                    <?php foreach ($sales_by_category as $category): ?>
                                                    <tr>
                                                        <td><?php echo ucfirst(htmlspecialchars($category['category'])); ?></td>
                                                        <td><?php echo number_format($category['items_sold']); ?></td>
                                                        <td>₱<?php echo number_format($category['revenue'], 2); ?></td>
                                                        <td><?php echo number_format($category['percentage'], 1); ?>%</td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center">No data available</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Top Selling Items -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Top Selling Items</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Medicine</th>
                                                    <th>Category</th>
                                                    <th>Quantity Sold</th>
                                                    <th>Revenue</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($top_selling_items)): ?>
                                                    <?php foreach ($top_selling_items as $item): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($item['medicine_name']); ?></td>
                                                        <td><?php echo ucfirst(htmlspecialchars($item['category'])); ?></td>
                                                        <td><?php echo number_format($item['quantity_sold']); ?></td>
                                                        <td>₱<?php echo number_format($item['revenue'], 2); ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center">No data available</td>
                                                    </tr>
                                                <?php endif; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function exportToExcel() {
            // In a real implementation, we'd use a library like SheetJS or a server-side solution
            alert('Exporting to Excel...');
            // Create form to submit the current report parameters to an export endpoint
            let form = document.createElement('form');
            form.method = 'POST';
            form.action = 'export_report.php?format=excel';
            
            // Add current filter parameters
            let params = new URLSearchParams(window.location.search);
            for (let [key, value] of params) {
                let input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                form.appendChild(input);
            }
            
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }

        function exportToPDF() {
            // In a real implementation, we'd use a library like JSPDF or a server-side solution
            alert('Exporting to PDF...');
            // Create form to submit the current report parameters to an export endpoint
            let form = document.createElement('form');
            form.method = 'POST';
            form.action = 'export_report.php?format=pdf';
            
            // Add current filter parameters
            let params = new URLSearchParams(window.location.search);
            for (let [key, value] of params) {
                let input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                form.appendChild(input);
            }
            
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
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
<?php
// Close database connection
$conn->close();
?> 