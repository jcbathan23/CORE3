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

// Check if table exists and create it if it doesn't
$check_table = "SHOW TABLES LIKE 'pharmacy_inventory'";
$result = $conn->query($check_table);

if ($result->num_rows == 0) {
    // Create the table
    $create_table = "CREATE TABLE pharmacy_inventory (
        medicine_id INT AUTO_INCREMENT PRIMARY KEY,
        medicine_name VARCHAR(255) NOT NULL,
        category ENUM('tablet', 'capsule', 'syrup', 'injection', 'cream') NOT NULL DEFAULT 'tablet',
        quantity INT NOT NULL,
        unit VARCHAR(50) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        manufacturer VARCHAR(255) NOT NULL,
        expiry_date DATE NOT NULL,
        storage_location VARCHAR(255) NOT NULL,
        status VARCHAR(20) DEFAULT 'In Stock'
    )";
    
    if (!$conn->query($create_table)) {
        die("Error creating table: " . $conn->error);
    }
} else {
    // Check if category column exists with correct type
    $check_column = "SHOW COLUMNS FROM pharmacy_inventory LIKE 'category'";
    $result = $conn->query($check_column);
    
    if ($result->num_rows == 0) {
        // Add category column
        $add_column = "ALTER TABLE pharmacy_inventory 
                      ADD COLUMN category ENUM('tablet', 'capsule', 'syrup', 'injection', 'cream') 
                      NOT NULL DEFAULT 'tablet'";
        if (!$conn->query($add_column)) {
            die("Error adding category column: " . $conn->error);
        }
        
        // Update existing records with random categories
        $update_categories = "UPDATE pharmacy_inventory 
                            SET category = ELT(FLOOR(1 + RAND() * 5), 
                            'tablet', 'capsule', 'syrup', 'injection', 'cream')";
        $conn->query($update_categories);
    }
}

// Process form submission for adding new medicine
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_medicine'])) {
    $medicine_name = $_POST['medicine_name'];
    $category = $_POST['category'];
    $quantity = $_POST['quantity'];
    $unit = $_POST['unit'];
    $price = $_POST['price'];
    $manufacturer = $_POST['manufacturer'];
    $expiry_date = $_POST['expiry_date'];
    $storage_location = $_POST['storage_location'];
    
    // Determine status based on quantity
    $status = 'In Stock';
    if ($quantity <= 0) {
        $status = 'Out of Stock';
    } elseif ($quantity <= 50) {
        $status = 'Low Stock';
    }
    
    // Prepare and execute SQL statement
    $stmt = $conn->prepare("INSERT INTO pharmacy_inventory (medicine_name, category, quantity, unit, price, manufacturer, expiry_date, storage_location, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssissssss", $medicine_name, $category, $quantity, $unit, $price, $manufacturer, $expiry_date, $storage_location, $status);
    
    if ($stmt->execute()) {
        $success_message = "Medicine added successfully!";
    } else {
        $error_message = "Error: " . $stmt->error;
    }
    
    $stmt->close();
}

// Process form submission for updating medicine
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_medicine'])) {
    $medicine_id = $_POST['medicine_id'];
    $quantity = $_POST['quantity'];
    $price = $_POST['price'];
    $storage_location = $_POST['storage_location'];
    
    // Determine status based on quantity
    $status = 'In Stock';
    if ($quantity <= 0) {
        $status = 'Out of Stock';
    } elseif ($quantity <= 50) {
        $status = 'Low Stock';
    }
    
    // Prepare and execute SQL statement
    $stmt = $conn->prepare("UPDATE pharmacy_inventory SET quantity = ?, price = ?, storage_location = ?, status = ? WHERE medicine_id = ?");
    $stmt->bind_param("idssi", $quantity, $price, $storage_location, $status, $medicine_id);
    
    if ($stmt->execute()) {
        $success_message = "Medicine updated successfully!";
    } else {
        $error_message = "Error: " . $stmt->error;
    }
    
    $stmt->close();
}

// Process medicine deletion
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $medicine_id = $_GET['id'];
    
    // Prepare and execute SQL statement
    $stmt = $conn->prepare("DELETE FROM pharmacy_inventory WHERE medicine_id = ?");
    $stmt->bind_param("i", $medicine_id);
    
    if ($stmt->execute()) {
        $success_message = "Medicine deleted successfully!";
    } else {
        $error_message = "Error: " . $stmt->error;
    }
    
    $stmt->close();
    header("Location: pharmacy_inventory.php");
    exit();
}

// Fetch inventory statistics
$total_items = 0;
$in_stock = 0;
$low_stock = 0;
$expired = 0;

$result = $conn->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'In Stock' THEN 1 ELSE 0 END) as in_stock,
    SUM(CASE WHEN status = 'Low Stock' THEN 1 ELSE 0 END) as low_stock,
    SUM(CASE WHEN status = 'Expired' OR expiry_date < CURDATE() THEN 1 ELSE 0 END) as expired
FROM pharmacy_inventory");

if ($result && $row = $result->fetch_assoc()) {
    $total_items = $row['total'];
    $in_stock = $row['in_stock'];
    $low_stock = $row['low_stock'];
    $expired = $row['expired'];
}

// Fetch all medicines
$medicines = [];
$result = $conn->query("SELECT * FROM pharmacy_inventory ORDER BY medicine_id DESC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $medicines[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Inventory</title>
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
                            <h5 class="mb-0">Pharmacy Inventory</h5>
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
                            
                            <!-- Inventory Overview -->
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="card bg-primary text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Total Items</h6>
                                            <h3 class="mb-0"><?php echo $total_items; ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-success text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">In Stock</h6>
                                            <h3 class="mb-0"><?php echo $in_stock; ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-warning text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Low Stock</h6>
                                            <h3 class="mb-0"><?php echo $low_stock; ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-danger text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Expired</h6>
                                            <h3 class="mb-0"><?php echo $expired; ?></h3>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Add Medicine Form -->
                            <form method="POST" class="mb-4">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Medicine Name</label>
                                        <input type="text" name="medicine_name" class="form-control" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Category</label>
                                        <select name="category" class="form-select" required>
                                            <option value="">Select Category</option>
                                            <option value="tablet">Tablet</option>
                                            <option value="capsule">Capsule</option>
                                            <option value="syrup">Syrup</option>
                                            <option value="injection">Injection</option>
                                            <option value="cream">Cream</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Quantity</label>
                                        <input type="number" name="quantity" class="form-control" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Unit</label>
                                        <select name="unit" class="form-select" required>
                                            <option value="pieces">Pieces</option>
                                            <option value="strips">Strips</option>
                                            <option value="bottles">Bottles</option>
                                            <option value="boxes">Boxes</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Price</label>
                                        <input type="number" step="0.01" name="price" class="form-control" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Manufacturer</label>
                                        <input type="text" name="manufacturer" class="form-control" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Expiry Date</label>
                                        <input type="date" name="expiry_date" class="form-control" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Storage Location</label>
                                        <input type="text" name="storage_location" class="form-control" required>
                                    </div>
                                </div>
                                <button type="submit" name="add_medicine" class="btn btn-primary">Add Medicine</button>
                            </form>

                            <!-- Inventory Table -->
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Medicine ID</th>
                                            <th>Name</th>
                                            <th>Category</th>
                                            <th>Quantity</th>
                                            <th>Unit</th>
                                            <th>Price</th>
                                            <th>Expiry Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($medicines as $medicine): ?>
                                        <tr>
                                            <td>M<?php echo str_pad($medicine['medicine_id'], 3, '0', STR_PAD_LEFT); ?></td>
                                            <td><?php echo htmlspecialchars($medicine['medicine_name']); ?></td>
                                            <td><?php echo ucfirst(htmlspecialchars($medicine['category'])); ?></td>
                                            <td><?php echo htmlspecialchars($medicine['quantity']); ?></td>
                                            <td><?php echo htmlspecialchars($medicine['unit']); ?></td>
                                            <td>₱<?php echo number_format($medicine['price'], 2); ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($medicine['expiry_date'])); ?></td>
                                            <td>
                                                <?php if ($medicine['status'] == 'In Stock'): ?>
                                                    <span class="badge bg-success">In Stock</span>
                                                <?php elseif ($medicine['status'] == 'Low Stock'): ?>
                                                    <span class="badge bg-warning">Low Stock</span>
                                                <?php elseif ($medicine['status'] == 'Out of Stock'): ?>
                                                    <span class="badge bg-danger">Out of Stock</span>
                                                <?php elseif ($medicine['status'] == 'Expired' || strtotime($medicine['expiry_date']) < time()): ?>
                                                    <span class="badge bg-danger">Expired</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="viewMedicine(<?php echo $medicine['medicine_id']; ?>, '<?php echo htmlspecialchars($medicine['medicine_name']); ?>', '<?php echo htmlspecialchars($medicine['category']); ?>', <?php echo $medicine['quantity']; ?>, '<?php echo htmlspecialchars($medicine['unit']); ?>', <?php echo $medicine['price']; ?>, '<?php echo htmlspecialchars($medicine['manufacturer']); ?>', '<?php echo $medicine['expiry_date']; ?>', '<?php echo htmlspecialchars($medicine['storage_location']); ?>')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-warning" onclick="updateStock(<?php echo $medicine['medicine_id']; ?>, <?php echo $medicine['quantity']; ?>, <?php echo $medicine['price']; ?>, '<?php echo htmlspecialchars($medicine['storage_location']); ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="pharmacy_inventory.php?delete=1&id=<?php echo $medicine['medicine_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this medicine?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($medicines)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center">No medicines found</td>
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

    <!-- View Medicine Modal -->
    <div class="modal fade" id="viewMedicineModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Medicine Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Medicine Information</label>
                        <p>Medicine ID: <span id="view_medicine_id"></span><br>
                        Name: <span id="view_medicine_name"></span><br>
                        Category: <span id="view_category"></span><br>
                        Quantity: <span id="view_quantity"></span><br>
                        Unit: <span id="view_unit"></span></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Additional Details</label>
                        <p>Price: ₱<span id="view_price"></span><br>
                        Manufacturer: <span id="view_manufacturer"></span><br>
                        Expiry Date: <span id="view_expiry_date"></span><br>
                        Storage Location: <span id="view_storage_location"></span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Stock Modal -->
    <div class="modal fade" id="updateStockModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="updateStockForm">
                        <input type="hidden" name="medicine_id" id="update_medicine_id" value="">
                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" id="update_quantity" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" step="0.01" name="price" id="update_price" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Storage Location</label>
                            <input type="text" name="storage_location" id="update_storage_location" class="form-control" required>
                        </div>
                        <button type="submit" name="update_medicine" class="btn btn-primary">Update Stock</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewMedicine(id, name, category, quantity, unit, price, manufacturer, expiryDate, storageLocation) {
            document.getElementById('view_medicine_id').textContent = 'M' + id.toString().padStart(3, '0');
            document.getElementById('view_medicine_name').textContent = name;
            document.getElementById('view_category').textContent = category.charAt(0).toUpperCase() + category.slice(1);
            document.getElementById('view_quantity').textContent = quantity;
            document.getElementById('view_unit').textContent = unit;
            document.getElementById('view_price').textContent = price.toFixed(2);
            document.getElementById('view_manufacturer').textContent = manufacturer;
            
            // Format expiry date
            const formattedDate = new Date(expiryDate);
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('view_expiry_date').textContent = formattedDate.toLocaleDateString(undefined, options);
            
            document.getElementById('view_storage_location').textContent = storageLocation;
            
            new bootstrap.Modal(document.getElementById('viewMedicineModal')).show();
        }

        function updateStock(id, quantity, price, storageLocation) {
            document.getElementById('update_medicine_id').value = id;
            document.getElementById('update_quantity').value = quantity;
            document.getElementById('update_price').value = price;
            document.getElementById('update_storage_location').value = storageLocation;
            
            new bootstrap.Modal(document.getElementById('updateStockModal')).show();
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