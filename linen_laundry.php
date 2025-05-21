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

// Function to check if a table exists
function tableExists($conn, $tableName) {
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    return $result->num_rows > 0;
}

// Check if any required table is missing
$requiredTables = ['linen_inventory', 'linen_laundry', 'linen_laundry_requests'];
$needToRecreateTables = false;

foreach ($requiredTables as $table) {
    if (!tableExists($conn, $table)) {
        $needToRecreateTables = true;
        break;
    }
}

if ($needToRecreateTables) {
    // Disable foreign key checks for the entire process
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");

    // Drop tables in correct order (child tables first, then parent)
    $dropTables = [
        "DROP TABLE IF EXISTS linen_laundry",
        "DROP TABLE IF EXISTS linen_laundry_requests",
        "DROP TABLE IF EXISTS linen_inventory"
    ];

    foreach ($dropTables as $dropTable) {
        if (!$conn->query($dropTable)) {
            echo "Error dropping table: " . $conn->error;
            $conn->query("SET FOREIGN_KEY_CHECKS = 1");
            exit();
        }
    }

    // 1. Create linen_inventory table first (parent table)
    $createInventoryTable = "CREATE TABLE `linen_inventory` (
        `linen_id` int(11) NOT NULL AUTO_INCREMENT,
        `item_name` varchar(100) NOT NULL,
        `quantity` int(11) NOT NULL DEFAULT 0,
        `condition` enum('New','Good','Fair','Poor') NOT NULL DEFAULT 'New',
        `status` enum('Available','In Use','In Laundry','Discarded') NOT NULL DEFAULT 'Available',
        `last_washed_date` datetime DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`linen_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    if (!$conn->query($createInventoryTable)) {
        echo "Error creating linen_inventory table: " . $conn->error;
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        exit();
    }

    // Add sample data to linen_inventory
    $sampleItems = [
        ['Bed Sheet', 100, 'New', 'Available'],
        ['Pillow Case', 150, 'Good', 'Available'],
        ['Blanket', 75, 'Good', 'Available'],
        ['Towel', 200, 'New', 'Available']
    ];

    $stmt = $conn->prepare("INSERT INTO linen_inventory (item_name, quantity, `condition`, status) VALUES (?, ?, ?, ?)");
    foreach ($sampleItems as $item) {
        $stmt->bind_param("siss", $item[0], $item[1], $item[2], $item[3]);
        if (!$stmt->execute()) {
            echo "Error inserting sample data: " . $stmt->error;
            $conn->query("SET FOREIGN_KEY_CHECKS = 1");
            exit();
        }
    }
    $stmt->close();

    // 2. Create linen_laundry_requests table (independent table)
    $createRequestsTable = "CREATE TABLE `linen_laundry_requests` (
        `request_id` int(11) NOT NULL AUTO_INCREMENT,
        `item_type` varchar(50) NOT NULL,
        `quantity` int(11) NOT NULL,
        `priority` enum('normal','urgent','emergency') NOT NULL DEFAULT 'normal',
        `request_type` enum('laundry','replacement') NOT NULL DEFAULT 'laundry',
        `status` enum('Pending','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
        `notes` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`request_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    if (!$conn->query($createRequestsTable)) {
        echo "Error creating linen_laundry_requests table: " . $conn->error;
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        exit();
    }

    // 3. Create linen_laundry table (child table with foreign key)
    $createLaundryTable = "CREATE TABLE `linen_laundry` (
        `laundry_id` int(11) NOT NULL AUTO_INCREMENT,
        `linen_id` int(11) NOT NULL,
        `sent_date` datetime NOT NULL,
        `return_date` datetime DEFAULT NULL,
        `status` enum('Sent','Processing','Returned') NOT NULL DEFAULT 'Sent',
        `notes` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`laundry_id`),
        KEY `linen_id` (`linen_id`),
        CONSTRAINT `linen_laundry_ibfk_1` FOREIGN KEY (`linen_id`) REFERENCES `linen_inventory` (`linen_id`) ON DELETE RESTRICT ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    if (!$conn->query($createLaundryTable)) {
        echo "Error creating linen_laundry table: " . $conn->error;
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        exit();
    }

    // Re-enable foreign key checks after all tables are created
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_request':
                $item_type = $_POST['item_type'];
                $quantity = $_POST['quantity'];
                $priority = $_POST['priority'];
                $request_type = $_POST['request_type'];
                $notes = $_POST['notes'];

                $sql = "INSERT INTO linen_laundry_requests (item_type, quantity, priority, request_type, notes, status, created_at) 
                        VALUES (?, ?, ?, ?, ?, 'Pending', NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sisss", $item_type, $quantity, $priority, $request_type, $notes);
                $stmt->execute();
                break;

            case 'update_status':
                $request_id = $_POST['request_id'];
                $status = $_POST['status'];
                $notes = $_POST['notes'];

                $sql = "UPDATE linen_laundry_requests 
                        SET status=?, notes=?, updated_at=NOW() 
                        WHERE request_id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssi", $status, $notes, $request_id);
                $stmt->execute();
                break;

            case 'cancel_request':
                $request_id = $_POST['request_id'];
                $sql = "UPDATE linen_laundry_requests 
                        SET status='Cancelled', updated_at=NOW() 
                        WHERE request_id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $request_id);
                $stmt->execute();
                break;
        }
    }
}

// Initialize variables
$result = false;
$stats = [
    'total_requests' => 0,
    'pending_requests' => 0,
    'in_progress_requests' => 0,
    'completed_requests' => 0
];

// Only fetch data if tables exist
if (tableExists($conn, 'linen_laundry_requests')) {
    // Fetch all laundry requests
    $result = $conn->query("SELECT * FROM linen_laundry_requests ORDER BY created_at DESC");

    // Fetch statistics
    $statsQuery = "SELECT 
        COUNT(*) as total_requests,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_requests,
        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress_requests,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_requests
    FROM linen_laundry_requests";
    
    $statsResult = $conn->query($statsQuery);
    if ($statsResult) {
        $stats = $statsResult->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Linen Laundry & Replacement</title>
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
                            <h5><i class="fas fa-tshirt me-2"></i>Linen Laundry & Replacement</h5>
                        </div>
                        <div class="card-body">
                            <!-- Statistics Overview -->
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="card stats-card bg-primary text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Total Requests</h6>
                                            <h3 class="mb-0"><?php echo $stats['total_requests']; ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card stats-card bg-warning text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Pending</h6>
                                            <h3 class="mb-0"><?php echo $stats['pending_requests']; ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card stats-card bg-info text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">In Progress</h6>
                                            <h3 class="mb-0"><?php echo $stats['in_progress_requests']; ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card stats-card bg-success text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Completed</h6>
                                            <h3 class="mb-0"><?php echo $stats['completed_requests']; ?></h3>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Laundry Request Form -->
                            <form method="POST" class="mb-4">
                                <input type="hidden" name="action" value="add_request">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Item Type</label>
                                        <select name="item_type" class="form-select" required>
                                            <option value="">Select Type</option>
                                            <option value="bed_sheet">Bed Sheet</option>
                                            <option value="pillow_case">Pillow Case</option>
                                            <option value="blanket">Blanket</option>
                                            <option value="towel">Towel</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Quantity</label>
                                        <input type="number" name="quantity" class="form-control" required min="1">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Priority</label>
                                        <select name="priority" class="form-select" required>
                                            <option value="normal">Normal</option>
                                            <option value="urgent">Urgent</option>
                                            <option value="emergency">Emergency</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Request Type</label>
                                        <select name="request_type" class="form-select" required>
                                            <option value="laundry">Laundry</option>
                                            <option value="replacement">Replacement</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Notes</label>
                                        <textarea name="notes" class="form-control" rows="2"></textarea>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Submit Request
                                </button>
                            </form>

                            <!-- Laundry Requests Table -->
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
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = $result->fetch_assoc()): ?>
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
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="viewRequest(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if($row['status'] !== 'Completed' && $row['status'] !== 'Cancelled'): ?>
                                                <button class="btn btn-sm btn-success" onclick="updateStatus(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="cancelRequest(<?php echo $row['request_id']; ?>)">
                                                    <i class="fas fa-times"></i>
                                                </button>
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
            </div>
        </div>
    </div>

    <!-- View Request Modal -->
    <div class="modal fade" id="viewRequestModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Request Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Request Information</label>
                        <p id="requestInfo"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Status Information</label>
                        <p id="statusInfo"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Notes</label>
                        <p id="requestNotes"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Update Request Status</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="updateStatusForm" method="POST" action="">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="request_id" id="update_request_id">
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Pending">Pending</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Status</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewRequest(request) {
            document.getElementById('requestInfo').innerHTML = `
                Request ID: ${request.request_id}<br>
                Item Type: ${request.item_type}<br>
                Quantity: ${request.quantity}<br>
                Priority: ${request.priority}<br>
                Request Type: ${request.request_type}
            `;
            document.getElementById('statusInfo').innerHTML = `
                Status: ${request.status}<br>
                Request Date: ${new Date(request.created_at).toLocaleDateString()}<br>
                Last Updated: ${request.updated_at ? new Date(request.updated_at).toLocaleDateString() : 'N/A'}
            `;
            document.getElementById('requestNotes').innerHTML = request.notes || 'No notes available';
            
            new bootstrap.Modal(document.getElementById('viewRequestModal')).show();
        }

        function updateStatus(request) {
            document.getElementById('update_request_id').value = request.request_id;
            document.getElementById('status').value = request.status;
            document.getElementById('notes').value = request.notes || '';
            
            new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
        }

        function cancelRequest(requestId) {
            if (confirm('Are you sure you want to cancel this request?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="cancel_request">
                    <input type="hidden" name="request_id" value="${requestId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
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