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

// Process form submission for creating a new order
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_order'])) {
    $supplier_id = $_POST['supplier_id'];
    $order_date = $_POST['order_date'];
    $expected_delivery_date = $_POST['expected_delivery_date'];
    $priority = $_POST['priority'];
    $notes = $_POST['notes'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Insert order
        $stmt = $conn->prepare("INSERT INTO pharmacy_orders (supplier_id, order_date, expected_delivery_date, priority, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $supplier_id, $order_date, $expected_delivery_date, $priority, $notes);
        $stmt->execute();
        
        $order_id = $conn->insert_id;
        
        // For a complete implementation, you would also handle order items here
        
        $conn->commit();
        $success_message = "Order created successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error: " . $e->getMessage();
    }
}

// Process order status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_order_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE pharmacy_orders SET status = ? WHERE order_id = ?");
    $stmt->bind_param("si", $status, $order_id);
    
    if ($stmt->execute()) {
        $success_message = "Order status updated to " . ucfirst($status) . "!";
    } else {
        $error_message = "Error: " . $stmt->error;
    }
    
    $stmt->close();
}

// Process order deletion
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $order_id = $_GET['id'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Delete order items first
        $stmt = $conn->prepare("DELETE FROM pharmacy_order_items WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        
        // Delete the order
        $stmt = $conn->prepare("DELETE FROM pharmacy_orders WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        
        $conn->commit();
        $success_message = "Order deleted successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error: " . $e->getMessage();
    }
}

// Fetch order statistics
$total_orders = 0;
$completed_orders = 0;
$pending_orders = 0;
$cancelled_orders = 0;

$result = $conn->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
FROM pharmacy_orders");

if ($result && $row = $result->fetch_assoc()) {
    $total_orders = $row['total'];
    $completed_orders = $row['completed'];
    $pending_orders = $row['pending'];
    $cancelled_orders = $row['cancelled'];
}

// Fetch all suppliers for the dropdown
$suppliers = [];
$result = $conn->query("SELECT supplier_id, company_name FROM pharmacy_suppliers WHERE status = 'active' ORDER BY company_name");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $suppliers[] = $row;
    }
}

// Fetch all orders
$orders = [];
$result = $conn->query("SELECT o.*, s.company_name 
                        FROM pharmacy_orders o 
                        JOIN pharmacy_suppliers s ON o.supplier_id = s.supplier_id 
                        ORDER BY o.order_date DESC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Orders</title>
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
                            <h5 class="mb-0">Pharmacy Orders</h5>
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
                            
                            <!-- Order Overview -->
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="card bg-primary text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Total Orders</h6>
                                            <h3 class="mb-0"><?php echo $total_orders; ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-success text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Completed</h6>
                                            <h3 class="mb-0"><?php echo $completed_orders; ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-warning text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Pending</h6>
                                            <h3 class="mb-0"><?php echo $pending_orders; ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-danger text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Cancelled</h6>
                                            <h3 class="mb-0"><?php echo $cancelled_orders; ?></h3>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- New Order Form -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Create New Order</h6>
                                </div>
                                <div class="card-body">
                                    <form id="newOrderForm" method="POST">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Supplier</label>
                                                <select class="form-select" name="supplier_id" required>
                                                    <option value="">Select Supplier</option>
                                                    <?php foreach($suppliers as $supplier): ?>
                                                    <option value="<?php echo $supplier['supplier_id']; ?>">
                                                        <?php echo htmlspecialchars($supplier['company_name']); ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Order Date</label>
                                                <input type="date" name="order_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Expected Delivery Date</label>
                                                <input type="date" name="expected_delivery_date" class="form-control" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Priority</label>
                                                <select class="form-select" name="priority" required>
                                                    <option value="low">Low</option>
                                                    <option value="medium" selected>Medium</option>
                                                    <option value="high">High</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Notes</label>
                                            <textarea class="form-control" name="notes" rows="3"></textarea>
                                        </div>
                                        <button type="submit" name="create_order" class="btn btn-primary">Create Order</button>
                                    </form>
                                </div>
                            </div>

                            <!-- Orders Table -->
                            <div class="card">
                                <div class="card-header">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <h6 class="mb-0">Recent Orders</h6>
                                        </div>
                                        <div class="col-auto">
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="orderSearch" placeholder="Search orders...">
                                                <button class="btn btn-outline-secondary" type="button" onclick="searchOrders()">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Order ID</th>
                                                    <th>Supplier</th>
                                                    <th>Order Date</th>
                                                    <th>Expected Delivery</th>
                                                    <th>Status</th>
                                                    <th>Priority</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($orders as $order): ?>
                                                <tr>
                                                    <td>#ORD<?php echo str_pad($order['order_id'], 3, '0', STR_PAD_LEFT); ?></td>
                                                    <td><?php echo htmlspecialchars($order['company_name']); ?></td>
                                                    <td><?php echo date('Y-m-d', strtotime($order['order_date'])); ?></td>
                                                    <td><?php echo date('Y-m-d', strtotime($order['expected_delivery_date'])); ?></td>
                                                    <td>
                                                        <?php if ($order['status'] == 'completed'): ?>
                                                            <span class="badge bg-success">Completed</span>
                                                        <?php elseif ($order['status'] == 'pending'): ?>
                                                            <span class="badge bg-warning">Pending</span>
                                                        <?php elseif ($order['status'] == 'cancelled'): ?>
                                                            <span class="badge bg-danger">Cancelled</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($order['priority'] == 'high'): ?>
                                                            <span class="badge bg-danger">High</span>
                                                        <?php elseif ($order['priority'] == 'medium'): ?>
                                                            <span class="badge bg-warning">Medium</span>
                                                        <?php elseif ($order['priority'] == 'low'): ?>
                                                            <span class="badge bg-info">Low</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-info" onclick="viewOrder(<?php echo $order['order_id']; ?>, '<?php echo htmlspecialchars($order['company_name']); ?>', '<?php echo $order['order_date']; ?>', '<?php echo $order['expected_delivery_date']; ?>', '<?php echo $order['status']; ?>', '<?php echo $order['priority']; ?>', '<?php echo htmlspecialchars(addslashes($order['notes'] ?? '')); ?>')">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <?php if ($order['status'] == 'pending'): ?>
                                                        <button class="btn btn-sm btn-primary" onclick="updateOrder(<?php echo $order['order_id']; ?>, '<?php echo $order['status']; ?>')">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <?php endif; ?>
                                                        <a href="pharmacy_orders.php?delete=1&id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this order?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <?php if (empty($orders)): ?>
                                                <tr>
                                                    <td colspan="7" class="text-center">No orders found</td>
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
        </div>
    </div>

    <!-- View Order Modal -->
    <div class="modal fade" id="viewOrderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Order Information</label>
                            <p>
                                Order ID: <span id="view_order_id"></span><br>
                                Supplier: <span id="view_supplier"></span><br>
                                Status: <span id="view_status"></span><br>
                                Priority: <span id="view_priority"></span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date Information</label>
                            <p>
                                Order Date: <span id="view_order_date"></span><br>
                                Expected Delivery: <span id="view_delivery_date"></span>
                            </p>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Notes</label>
                        <p id="view_notes"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Order Status Modal -->
    <div class="modal fade" id="updateOrderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Order Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="updateOrderForm">
                        <input type="hidden" name="order_id" id="update_order_id">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="update_status" class="form-select" required>
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <button type="submit" name="update_order_status" class="btn btn-primary">Update Status</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewOrder(orderId, supplier, orderDate, deliveryDate, status, priority, notes) {
            document.getElementById('view_order_id').textContent = '#ORD' + orderId.toString().padStart(3, '0');
            document.getElementById('view_supplier').textContent = supplier;
            
            // Format dates
            const formattedOrderDate = new Date(orderDate);
            const formattedDeliveryDate = new Date(deliveryDate);
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            
            document.getElementById('view_order_date').textContent = formattedOrderDate.toLocaleDateString(undefined, options);
            document.getElementById('view_delivery_date').textContent = formattedDeliveryDate.toLocaleDateString(undefined, options);
            
            // Status
            let statusHtml = '';
            if (status === 'completed') {
                statusHtml = '<span class="badge bg-success">Completed</span>';
            } else if (status === 'pending') {
                statusHtml = '<span class="badge bg-warning">Pending</span>';
            } else if (status === 'cancelled') {
                statusHtml = '<span class="badge bg-danger">Cancelled</span>';
            }
            document.getElementById('view_status').innerHTML = statusHtml;
            
            // Priority
            let priorityHtml = '';
            if (priority === 'high') {
                priorityHtml = '<span class="badge bg-danger">High</span>';
            } else if (priority === 'medium') {
                priorityHtml = '<span class="badge bg-warning">Medium</span>';
            } else if (priority === 'low') {
                priorityHtml = '<span class="badge bg-info">Low</span>';
            }
            document.getElementById('view_priority').innerHTML = priorityHtml;
            
            // Notes
            document.getElementById('view_notes').textContent = notes || 'No notes available';
            
            const modal = new bootstrap.Modal(document.getElementById('viewOrderModal'));
            modal.show();
        }

        function updateOrder(orderId, currentStatus) {
            document.getElementById('update_order_id').value = orderId;
            document.getElementById('update_status').value = currentStatus;
            
            const modal = new bootstrap.Modal(document.getElementById('updateOrderModal'));
            modal.show();
        }

        function searchOrders() {
            const input = document.getElementById('orderSearch').value.toLowerCase();
            const table = document.querySelector('table');
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(input)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
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