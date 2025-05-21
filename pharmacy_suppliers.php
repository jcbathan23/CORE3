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

// Process form submission for adding a new supplier
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_supplier'])) {
    $company_name = $_POST['company_name'];
    $contact_person = $_POST['contact_person'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $status = $_POST['status'];
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT supplier_id FROM pharmacy_suppliers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $error_message = "Error: A supplier with this email already exists!";
    } else {
        // Prepare and execute SQL statement
        $stmt = $conn->prepare("INSERT INTO pharmacy_suppliers (company_name, contact_person, email, phone, address, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $company_name, $contact_person, $email, $phone, $address, $status);
        
        if ($stmt->execute()) {
            $success_message = "Supplier added successfully!";
        } else {
            $error_message = "Error: " . $stmt->error;
        }
    }
    
    $stmt->close();
}

// Process form submission for updating a supplier
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_supplier'])) {
    $supplier_id = $_POST['supplier_id'];
    $company_name = $_POST['company_name'];
    $contact_person = $_POST['contact_person'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $status = $_POST['status'];
    
    // Check if email already exists for another supplier
    $stmt = $conn->prepare("SELECT supplier_id FROM pharmacy_suppliers WHERE email = ? AND supplier_id != ?");
    $stmt->bind_param("si", $email, $supplier_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $error_message = "Error: Another supplier with this email already exists!";
    } else {
        // Prepare and execute SQL statement
        $stmt = $conn->prepare("UPDATE pharmacy_suppliers SET company_name = ?, contact_person = ?, email = ?, phone = ?, address = ?, status = ? WHERE supplier_id = ?");
        $stmt->bind_param("ssssssi", $company_name, $contact_person, $email, $phone, $address, $status, $supplier_id);
        
        if ($stmt->execute()) {
            $success_message = "Supplier updated successfully!";
        } else {
            $error_message = "Error: " . $stmt->error;
        }
    }
    
    $stmt->close();
}

// Process supplier deletion
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $supplier_id = $_GET['id'];
    
    // Check if supplier has related orders
    $stmt = $conn->prepare("SELECT COUNT(*) as order_count FROM pharmacy_orders WHERE supplier_id = ?");
    $stmt->bind_param("i", $supplier_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['order_count'] > 0) {
        $error_message = "Error: Cannot delete supplier with existing orders. Please delete the orders first or mark the supplier as inactive.";
    } else {
        // Prepare and execute SQL statement
        $stmt = $conn->prepare("DELETE FROM pharmacy_suppliers WHERE supplier_id = ?");
        $stmt->bind_param("i", $supplier_id);
        
        if ($stmt->execute()) {
            $success_message = "Supplier deleted successfully!";
        } else {
            $error_message = "Error: " . $stmt->error;
        }
    }
    
    $stmt->close();
}

// Fetch supplier statistics
$total_suppliers = 0;
$active_suppliers = 0;
$pending_orders = 0;
$total_products = 0;

$result = $conn->query("SELECT 
    COUNT(*) as total_suppliers,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_suppliers
FROM pharmacy_suppliers");

if ($result && $row = $result->fetch_assoc()) {
    $total_suppliers = $row['total_suppliers'];
    $active_suppliers = $row['active_suppliers'];
}

$result = $conn->query("SELECT COUNT(*) as pending_orders FROM pharmacy_orders WHERE status = 'pending'");
if ($result && $row = $result->fetch_assoc()) {
    $pending_orders = $row['pending_orders'];
}

$result = $conn->query("SELECT COUNT(*) as total_products FROM pharmacy_inventory");
if ($result && $row = $result->fetch_assoc()) {
    $total_products = $row['total_products'];
}

// Fetch all suppliers
$suppliers = [];
$result = $conn->query("SELECT * FROM pharmacy_suppliers ORDER BY company_name");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $suppliers[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Suppliers</title>
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
                            <h5 class="mb-0">Pharmacy Suppliers</h5>
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
                            
                            <!-- Supplier Overview -->
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="card bg-primary text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Total Suppliers</h6>
                                            <h3 class="mb-0"><?php echo $total_suppliers; ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-success text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Active Suppliers</h6>
                                            <h3 class="mb-0"><?php echo $active_suppliers; ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-warning text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Pending Orders</h6>
                                            <h3 class="mb-0"><?php echo $pending_orders; ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-info text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Total Products</h6>
                                            <h3 class="mb-0"><?php echo $total_products; ?></h3>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Add New Supplier Form -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Add New Supplier</h6>
                                </div>
                                <div class="card-body">
                                    <form id="newSupplierForm" method="POST">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Company Name</label>
                                                <input type="text" name="company_name" class="form-control" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Contact Person</label>
                                                <input type="text" name="contact_person" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Email</label>
                                                <input type="email" name="email" class="form-control" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Phone</label>
                                                <input type="tel" name="phone" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Address</label>
                                                <textarea name="address" class="form-control" rows="3" required></textarea>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Status</label>
                                                <select name="status" class="form-select" required>
                                                    <option value="active">Active</option>
                                                    <option value="inactive">Inactive</option>
                                                </select>
                                            </div>
                                        </div>
                                        <button type="submit" name="add_supplier" class="btn btn-primary">Add Supplier</button>
                                    </form>
                                </div>
                            </div>

                            <!-- Suppliers Table -->
                            <div class="card">
                                <div class="card-header">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <h6 class="mb-0">Supplier List</h6>
                                        </div>
                                        <div class="col-auto">
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="supplierSearch" placeholder="Search suppliers...">
                                                <button class="btn btn-outline-secondary" type="button" onclick="searchSuppliers()">
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
                                                    <th>Supplier ID</th>
                                                    <th>Company Name</th>
                                                    <th>Contact Person</th>
                                                    <th>Email</th>
                                                    <th>Phone</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($suppliers as $supplier): ?>
                                                <tr>
                                                    <td>#SUP<?php echo str_pad($supplier['supplier_id'], 3, '0', STR_PAD_LEFT); ?></td>
                                                    <td><?php echo htmlspecialchars($supplier['company_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($supplier['contact_person']); ?></td>
                                                    <td><?php echo htmlspecialchars($supplier['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($supplier['phone']); ?></td>
                                                    <td>
                                                        <?php if ($supplier['status'] == 'active'): ?>
                                                            <span class="badge bg-success">Active</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Inactive</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-info" onclick="viewSupplier(<?php echo $supplier['supplier_id']; ?>, '<?php echo htmlspecialchars(addslashes($supplier['company_name'])); ?>', '<?php echo htmlspecialchars(addslashes($supplier['contact_person'])); ?>', '<?php echo htmlspecialchars(addslashes($supplier['email'])); ?>', '<?php echo htmlspecialchars(addslashes($supplier['phone'])); ?>', '<?php echo htmlspecialchars(addslashes($supplier['address'])); ?>', '<?php echo $supplier['status']; ?>')">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-primary" onclick="editSupplier(<?php echo $supplier['supplier_id']; ?>, '<?php echo htmlspecialchars(addslashes($supplier['company_name'])); ?>', '<?php echo htmlspecialchars(addslashes($supplier['contact_person'])); ?>', '<?php echo htmlspecialchars(addslashes($supplier['email'])); ?>', '<?php echo htmlspecialchars(addslashes($supplier['phone'])); ?>', '<?php echo htmlspecialchars(addslashes($supplier['address'])); ?>', '<?php echo $supplier['status']; ?>')">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <a href="pharmacy_suppliers.php?delete=1&id=<?php echo $supplier['supplier_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this supplier?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <?php if (empty($suppliers)): ?>
                                                <tr>
                                                    <td colspan="7" class="text-center">No suppliers found</td>
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

    <!-- View Supplier Modal -->
    <div class="modal fade" id="viewSupplierModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Supplier Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Company Information</label>
                            <p>
                                Supplier ID: <span id="view_supplier_id"></span><br>
                                Company Name: <span id="view_company_name"></span><br>
                                Status: <span id="view_status"></span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Contact Information</label>
                            <p>
                                Contact Person: <span id="view_contact_person"></span><br>
                                Email: <span id="view_email"></span><br>
                                Phone: <span id="view_phone"></span>
                            </p>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Address</label>
                        <p id="view_address"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Supplier Modal -->
    <div class="modal fade" id="editSupplierModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="editSupplierForm">
                        <input type="hidden" name="supplier_id" id="edit_supplier_id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Company Name</label>
                                <input type="text" name="company_name" id="edit_company_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Person</label>
                                <input type="text" name="contact_person" id="edit_contact_person" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" id="edit_email" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" name="phone" id="edit_phone" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" id="edit_address" class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" id="edit_status" class="form-select" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" name="edit_supplier" class="btn btn-primary">Update Supplier</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewSupplier(supplierId, companyName, contactPerson, email, phone, address, status) {
            document.getElementById('view_supplier_id').textContent = '#SUP' + supplierId.toString().padStart(3, '0');
            document.getElementById('view_company_name').textContent = companyName;
            document.getElementById('view_contact_person').textContent = contactPerson;
            document.getElementById('view_email').textContent = email;
            document.getElementById('view_phone').textContent = phone;
            document.getElementById('view_address').textContent = address;
            
            // Status badge
            if (status === 'active') {
                document.getElementById('view_status').innerHTML = '<span class="badge bg-success">Active</span>';
            } else {
                document.getElementById('view_status').innerHTML = '<span class="badge bg-danger">Inactive</span>';
            }
            
            const modal = new bootstrap.Modal(document.getElementById('viewSupplierModal'));
            modal.show();
        }

        function editSupplier(supplierId, companyName, contactPerson, email, phone, address, status) {
            document.getElementById('edit_supplier_id').value = supplierId;
            document.getElementById('edit_company_name').value = companyName;
            document.getElementById('edit_contact_person').value = contactPerson;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_phone').value = phone;
            document.getElementById('edit_address').value = address;
            document.getElementById('edit_status').value = status;
            
            const modal = new bootstrap.Modal(document.getElementById('editSupplierModal'));
            modal.show();
        }

        function searchSuppliers() {
            const input = document.getElementById('supplierSearch').value.toLowerCase();
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