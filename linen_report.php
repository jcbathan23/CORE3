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

// Get date range from request
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Initialize all variables
$inventory_summary = [
    'total_items' => 0,
    'available_items' => 0,
    'in_use_items' => 0,
    'in_laundry_items' => 0,
    'discarded_items' => 0
];

$laundry_stats = [
    'total_laundry' => 0,
    'sent_items' => 0,
    'processing_items' => 0,
    'returned_items' => 0
];

$request_stats = [
    'total_requests' => 0,
    'pending_requests' => 0,
    'in_progress_requests' => 0,
    'completed_requests' => 0
];

// Initialize result sets
$recent_activities = false;
$recent_requests = false;

// Check if tables exist before querying
$tables_exist = true;
$required_tables = ['linen_inventory', 'linen_laundry', 'linen_laundry_requests'];
foreach ($required_tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows == 0) {
        $tables_exist = false;
        break;
    }
}

if ($tables_exist) {
    // Fetch inventory summary
    $sql = "SELECT 
                COUNT(*) as total_items,
                SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) as available_items,
                SUM(CASE WHEN status = 'In Use' THEN 1 ELSE 0 END) as in_use_items,
                SUM(CASE WHEN status = 'In Laundry' THEN 1 ELSE 0 END) as in_laundry_items,
                SUM(CASE WHEN status = 'Discarded' THEN 1 ELSE 0 END) as discarded_items
            FROM linen_inventory";
    $result = $conn->query($sql);
    if ($result) {
        $inventory_summary = $result->fetch_assoc();
    }

    // Fetch laundry statistics
    $sql = "SELECT 
                COUNT(*) as total_laundry,
                SUM(CASE WHEN status = 'Sent' THEN 1 ELSE 0 END) as sent_items,
                SUM(CASE WHEN status = 'Processing' THEN 1 ELSE 0 END) as processing_items,
                SUM(CASE WHEN status = 'Returned' THEN 1 ELSE 0 END) as returned_items
            FROM linen_laundry
            WHERE sent_date BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $laundry_stats = $result->fetch_assoc();
        }
        $stmt->close();
    }

    // Fetch recent laundry activities
    $sql = "SELECT l.*, i.item_name, i.quantity, i.condition 
            FROM linen_laundry l 
            JOIN linen_inventory i ON l.linen_id = i.linen_id 
            WHERE l.sent_date BETWEEN ? AND ?
            ORDER BY l.sent_date DESC";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $recent_activities = $stmt->get_result();
        $stmt->close();
    } else {
        $recent_activities = false;
    }

    // Fetch laundry requests statistics
    $sql = "SELECT 
                COUNT(*) as total_requests,
                SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_requests,
                SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress_requests,
                SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_requests
            FROM linen_laundry_requests
            WHERE created_at BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $request_stats = $result->fetch_assoc();
        }
        $stmt->close();
    }

    // Fetch recent laundry requests
    $sql = "SELECT * FROM linen_laundry_requests 
            WHERE created_at BETWEEN ? AND ?
            ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $recent_requests = $stmt->get_result();
        $stmt->close();
    } else {
        $recent_requests = false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Linen Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
            border: none;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .card-header {
            background-color: #4A628A;
            color: white;
            padding: 15px 20px;
        }

        .card-header h5 {
            margin: 0;
            font-size: 1.2rem;
        }

        .stats-card {
            border-radius: 10px;
            transition: transform 0.2s;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-card .card-body {
            padding: 1.5rem;
        }

        .stats-card .card-title {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stats-card h3 {
            font-size: 2rem;
            font-weight: 600;
            margin: 0.5rem 0 0;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .badge {
            padding: 0.5em 0.75em;
            font-weight: 500;
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
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5><i class="fas fa-chart-bar me-2"></i>Linen Management Report</h5>
                        </div>
                        <div class="card-body">
                            <!-- Date Range Filter -->
                            <form method="GET" class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" 
                                           value="<?php echo $start_date; ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" 
                                           value="<?php echo $end_date; ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary d-block w-100">
                                        <i class="fas fa-sync-alt me-2"></i>Generate Report
                                    </button>
                                </div>
                            </form>

                            <!-- Inventory Summary -->
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="card stats-card bg-primary text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Total Items</h6>
                                            <h3 class="mb-0"><?php echo $inventory_summary['total_items']; ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card stats-card bg-success text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Available</h6>
                                            <h3 class="mb-0"><?php echo $inventory_summary['available_items']; ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card stats-card bg-warning text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">In Use</h6>
                                            <h3 class="mb-0"><?php echo $inventory_summary['in_use_items']; ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card stats-card bg-danger text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">In Laundry</h6>
                                            <h3 class="mb-0"><?php echo $inventory_summary['in_laundry_items']; ?></h3>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Laundry Statistics -->
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="card stats-card bg-info text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Total Laundry</h6>
                                            <h3 class="mb-0"><?php echo $laundry_stats['total_laundry']; ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card stats-card bg-warning text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Sent</h6>
                                            <h3 class="mb-0"><?php echo $laundry_stats['sent_items']; ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card stats-card bg-primary text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Processing</h6>
                                            <h3 class="mb-0"><?php echo $laundry_stats['processing_items']; ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card stats-card bg-success text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Returned</h6>
                                            <h3 class="mb-0"><?php echo $laundry_stats['returned_items']; ?></h3>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Laundry Requests Statistics -->
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="card stats-card bg-secondary text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Total Requests</h6>
                                            <h3 class="mb-0"><?php echo $request_stats['total_requests']; ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card stats-card bg-warning text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Pending</h6>
                                            <h3 class="mb-0"><?php echo $request_stats['pending_requests']; ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card stats-card bg-info text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">In Progress</h6>
                                            <h3 class="mb-0"><?php echo $request_stats['in_progress_requests']; ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card stats-card bg-success text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Completed</h6>
                                            <h3 class="mb-0"><?php echo $request_stats['completed_requests']; ?></h3>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Activities -->
                            <div class="card">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0 text-dark">Recent Laundry Activities</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!$tables_exist): ?>
                                        <div class="alert alert-info">
                                            Required tables have not been created yet. Please set up the system first.
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Laundry ID</th>
                                                        <th>Item Name</th>
                                                        <th>Quantity</th>
                                                        <th>Condition</th>
                                                        <th>Sent Date</th>
                                                        <th>Return Date</th>
                                                        <th>Status</th>
                                                        <th>Notes</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if ($recent_activities && $recent_activities->num_rows > 0): ?>
                                                        <?php while($row = $recent_activities->fetch_assoc()): ?>
                                                        <tr>
                                                            <td><?php echo $row['laundry_id']; ?></td>
                                                            <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                                                            <td><?php echo $row['quantity']; ?></td>
                                                            <td><?php echo htmlspecialchars($row['condition']); ?></td>
                                                            <td><?php echo date('Y-m-d', strtotime($row['sent_date'])); ?></td>
                                                            <td><?php echo $row['return_date'] ? date('Y-m-d', strtotime($row['return_date'])) : 'Pending'; ?></td>
                                                            <td>
                                                                <span class="badge bg-<?php 
                                                                    echo $row['status'] === 'Sent' ? 'warning' : 
                                                                        ($row['status'] === 'Processing' ? 'info' : 
                                                                        ($row['status'] === 'Returned' ? 'success' : 'secondary')); 
                                                                ?>">
                                                                    <?php echo htmlspecialchars($row['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($row['notes'] ?? ''); ?></td>
                                                        </tr>
                                                        <?php endwhile; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="8" class="text-center">No laundry activities found for the selected date range.</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Recent Requests -->
                            <div class="card mt-4">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0 text-dark">Recent Laundry Requests</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!$tables_exist): ?>
                                        <div class="alert alert-info">
                                            Required tables have not been created yet. Please set up the system first.
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Request ID</th>
                                                        <th>Item Type</th>
                                                        <th>Quantity</th>
                                                        <th>Priority</th>
                                                        <th>Request Type</th>
                                                        <th>Status</th>
                                                        <th>Request Date</th>
                                                        <th>Notes</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if ($recent_requests && $recent_requests->num_rows > 0): ?>
                                                        <?php while($row = $recent_requests->fetch_assoc()): ?>
                                                        <tr>
                                                            <td><?php echo $row['request_id']; ?></td>
                                                            <td><?php echo htmlspecialchars($row['item_type']); ?></td>
                                                            <td><?php echo $row['quantity']; ?></td>
                                                            <td>
                                                                <span class="badge bg-<?php 
                                                                    echo $row['priority'] === 'urgent' ? 'danger' : 
                                                                        ($row['priority'] === 'emergency' ? 'warning' : 'success'); 
                                                                ?>">
                                                                    <?php echo ucfirst(htmlspecialchars($row['priority'])); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo ucfirst(htmlspecialchars($row['request_type'])); ?></td>
                                                            <td>
                                                                <span class="badge bg-<?php 
                                                                    echo $row['status'] === 'Pending' ? 'warning' : 
                                                                        ($row['status'] === 'In Progress' ? 'info' : 
                                                                        ($row['status'] === 'Completed' ? 'success' : 'secondary')); 
                                                                ?>">
                                                                    <?php echo htmlspecialchars($row['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></td>
                                                            <td><?php echo htmlspecialchars($row['notes'] ?? ''); ?></td>
                                                        </tr>
                                                        <?php endwhile; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="8" class="text-center">No laundry requests found for the selected date range.</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
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