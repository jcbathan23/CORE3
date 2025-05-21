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

// Create ward_beds table if it doesn't exist
$tableCheck = $conn->query("SHOW TABLES LIKE 'ward_beds'");
if ($tableCheck->num_rows == 0) {
    $createTable = "CREATE TABLE `ward_beds` (
        `bed_id` int(11) NOT NULL AUTO_INCREMENT,
        `ward_id` int(11) NOT NULL,
        `room_number` varchar(10) NOT NULL,
        `bed_number` varchar(10) NOT NULL,
        `status` enum('available','occupied','maintenance') NOT NULL DEFAULT 'available',
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`bed_id`),
        UNIQUE KEY `room_bed` (`room_number`,`bed_number`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    if (!$conn->query($createTable)) {
        echo "Error creating ward_beds table: " . $conn->error;
    } else {
        // Add sample bed data
        $sampleBeds = [
            [1, '101', 'A1', 'available'],
            [1, '101', 'A2', 'occupied'],
            [1, '102', 'B1', 'available'],
            [2, '201', 'A1', 'occupied'],
            [2, '201', 'A2', 'available'],
            [3, '301', 'A1', 'occupied']
        ];
        
        $stmt = $conn->prepare("INSERT INTO ward_beds (ward_id, room_number, bed_number, status) VALUES (?, ?, ?, ?)");
        foreach ($sampleBeds as $bed) {
            $stmt->bind_param("isss", $bed[0], $bed[1], $bed[2], $bed[3]);
            $stmt->execute();
        }
        $stmt->close();
    }
}

// Create bed_allocations table if it doesn't exist
$tableCheck = $conn->query("SHOW TABLES LIKE 'bed_allocations'");
if ($tableCheck->num_rows == 0) {
    $createTable = "CREATE TABLE `bed_allocations` (
        `allocation_id` int(11) NOT NULL AUTO_INCREMENT,
        `room_number` varchar(10) NOT NULL,
        `bed_number` varchar(10) NOT NULL,
        `patient_id` int(11) NOT NULL,
        `admission_date` date NOT NULL,
        `expected_discharge` date NOT NULL,
        `status` enum('occupied','discharged','transferred') NOT NULL DEFAULT 'occupied',
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`allocation_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    if (!$conn->query($createTable)) {
        echo "Error creating bed_allocations table: " . $conn->error;
    } else {
        // Add sample allocations
        $sampleAllocations = [
            ['101', 'A2', 1, '2024-03-01', '2024-03-10', 'occupied'],
            ['201', 'A1', 2, '2024-03-02', '2024-03-15', 'occupied'],
            ['301', 'A1', 3, '2024-03-03', '2024-03-08', 'occupied']
        ];
        
        $stmt = $conn->prepare("INSERT INTO bed_allocations (room_number, bed_number, patient_id, admission_date, expected_discharge, status) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($sampleAllocations as $allocation) {
            $stmt->bind_param("ssisss", $allocation[0], $allocation[1], $allocation[2], $allocation[3], $allocation[4], $allocation[5]);
            $stmt->execute();
        }
        $stmt->close();
    }
}

// Filter variables
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$ward_filter = isset($_GET['ward']) ? $_GET['ward'] : '';

// Get overall statistics
$stats = [
    'total_patients' => 0,
    'available_beds' => 0,
    'nurses_on_duty' => 0,
    'avg_stay_days' => 0
];

// Total patients currently admitted
$query = "
    SELECT COUNT(*) as total
    FROM bed_allocations
    WHERE status = 'occupied'
";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $stats['total_patients'] = $row['total'];
}

// Available beds
$query = "
    SELECT
        (SELECT COUNT(*) FROM bed_allocations WHERE status = 'occupied') as occupied,
        (SELECT 55) as total_beds
";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $stats['available_beds'] = $row['total_beds'] - $row['occupied'];
}

// Nurses on duty
$query = "
    SELECT COUNT(*) as total
    FROM nurse_assignments
    WHERE status = 'active'
    AND start_date <= CURDATE() AND end_date >= CURDATE()
";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $stats['nurses_on_duty'] = $row['total'];
}

// Average stay days
$query = "
    SELECT AVG(DATEDIFF(expected_discharge, admission_date)) as avg_stay
    FROM bed_allocations
    WHERE status = 'occupied'
";
$result = $conn->query($query);
$stats['avg_stay_days'] = 0; // Initialize with default value
if ($result && ($row = $result->fetch_assoc()) && !is_null($row['avg_stay'])) {
    $stats['avg_stay_days'] = round($row['avg_stay'], 1);
}

// Get ward-wise statistics
$wards_stats = [];

// First, get all wards
$query = "SELECT ward_id, ward_name, capacity FROM wards";
$result = $conn->query($query);

// If no wards found or query fails, use sample data
if (!$result || $result->num_rows == 0) {
    $wards_stats = [
        [
            'ward_name' => 'General Ward',
            'total_beds' => 30,
            'occupied' => 22,
            'available' => 8,
            'nurses' => 6,
            'avg_stay' => 3.5
        ],
        [
            'ward_name' => 'ICU',
            'total_beds' => 10,
            'occupied' => 8,
            'available' => 2,
            'nurses' => 4,
            'avg_stay' => 5.2
        ],
        [
            'ward_name' => 'Emergency',
            'total_beds' => 15,
            'occupied' => 15,
            'available' => 0,
            'nurses' => 2,
            'avg_stay' => 1.8
        ]
    ];
} else {
    while ($ward = $result->fetch_assoc()) {
        $ward_id = $ward['ward_id'];
        $ward_stats = [
            'ward_name' => $ward['ward_name'],
            'total_beds' => $ward['capacity'],
            'occupied' => 0,
            'available' => 0,
            'nurses' => 0,
            'avg_stay' => 0
        ];
        
        // Get occupied beds
        $query = "
            SELECT COUNT(*) as occupied
            FROM bed_allocations ba
            INNER JOIN ward_beds wb ON ba.room_number = wb.room_number AND ba.bed_number = wb.bed_number
            WHERE ba.status = 'occupied' AND wb.ward_id = ?
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $ward_id);
        $stmt->execute();
        $bed_result = $stmt->get_result();
        if ($bed_result && $bed_row = $bed_result->fetch_assoc()) {
            $ward_stats['occupied'] = $bed_row['occupied'];
            $ward_stats['available'] = $ward['capacity'] - $bed_row['occupied'];
        }
        
        // Get nurses count
        $query = "
            SELECT COUNT(*) as nurses
            FROM nurse_assignments
            WHERE ward_id = ? AND status = 'active'
            AND start_date <= CURDATE() AND end_date >= CURDATE()
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $ward_id);
        $stmt->execute();
        $nurse_result = $stmt->get_result();
        if ($nurse_result && $nurse_row = $nurse_result->fetch_assoc()) {
            $ward_stats['nurses'] = $nurse_row['nurses'];
        }
        
        // Get average stay
        $query = "
            SELECT AVG(DATEDIFF(expected_discharge, admission_date)) as avg_stay
            FROM bed_allocations ba
            INNER JOIN ward_beds wb ON ba.room_number = wb.room_number AND ba.bed_number = wb.bed_number
            WHERE ba.status = 'occupied' AND wb.ward_id = ?
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $ward_id);
        $stmt->execute();
        $stay_result = $stmt->get_result();
        if ($stay_result && ($stay_row = $stay_result->fetch_assoc()) && !is_null($stay_row['avg_stay'])) {
            $ward_stats['avg_stay'] = round($stay_row['avg_stay'], 1);
        }
        
        $wards_stats[] = $ward_stats;
        $stmt->close();
    }
}

// Get supply stats
$supply_stats = [
    'linen' => ['total' => 0, 'available' => 0, 'low' => 0, 'out' => 0],
    'medical' => ['total' => 0, 'available' => 0, 'low' => 0, 'out' => 0],
    'equipment' => ['total' => 0, 'available' => 0, 'low' => 0, 'out' => 0]
];

$query = "
    SELECT 
        item_type,
        COUNT(*) as total,
        SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
        SUM(CASE WHEN status = 'low' THEN 1 ELSE 0 END) as low_stock,
        SUM(CASE WHEN status = 'out' THEN 1 ELSE 0 END) as out_of_stock
    FROM ward_supplies
    GROUP BY item_type
";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $type = $row['item_type'];
        if (isset($supply_stats[$type])) {
            $supply_stats[$type]['total'] = $row['total'];
            $supply_stats[$type]['available'] = $row['available'];
            $supply_stats[$type]['low'] = $row['low_stock'];
            $supply_stats[$type]['out'] = $row['out_of_stock'];
        }
    }
}

function generatePDF() {
    // PDF export code would go here
    return true;
}

function generateExcel() {
    // Excel export code would go here
    return true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ward Reports</title>
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
                            <h5 class="mb-0">Ward Reports</h5>
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
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Ward</label>
                                        <select name="ward" class="form-select">
                                            <option value="">All Wards</option>
                                            <?php foreach ($wards_stats as $ward): ?>
                                                <option value="<?php echo $ward['ward_name']; ?>" <?php echo $ward_filter === $ward['ward_name'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($ward['ward_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Generate Report</button>
                            </form>

                            <!-- Overall Statistics -->
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="card bg-primary text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Total Patients</h6>
                                            <h3 class="mb-0"><?php echo $stats['total_patients']; ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-success text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Available Beds</h6>
                                            <h3 class="mb-0"><?php echo $stats['available_beds']; ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-warning text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Nurses on Duty</h6>
                                            <h3 class="mb-0"><?php echo $stats['nurses_on_duty']; ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-info text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Average Stay (Days)</h6>
                                            <h3 class="mb-0"><?php echo $stats['avg_stay_days']; ?></h3>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Ward-wise Statistics -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Ward-wise Statistics</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Ward</th>
                                                    <th>Total Beds</th>
                                                    <th>Occupied</th>
                                                    <th>Available</th>
                                                    <th>Nurses</th>
                                                    <th>Avg. Stay</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($wards_stats as $ward): ?>
                                                    <?php if (empty($ward_filter) || $ward_filter === $ward['ward_name']): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($ward['ward_name']); ?></td>
                                                            <td><?php echo $ward['total_beds']; ?></td>
                                                            <td><?php echo $ward['occupied']; ?></td>
                                                            <td><?php echo $ward['available']; ?></td>
                                                            <td><?php echo $ward['nurses']; ?></td>
                                                            <td><?php echo $ward['avg_stay']; ?> days</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Supply Status -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Supply Status</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Item Type</th>
                                                    <th>Total Items</th>
                                                    <th>Available</th>
                                                    <th>Low Stock</th>
                                                    <th>Out of Stock</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($supply_stats as $type => $stats): ?>
                                                    <tr>
                                                        <td><?php echo ucfirst($type); ?></td>
                                                        <td><?php echo $stats['total']; ?></td>
                                                        <td><?php echo $stats['available']; ?></td>
                                                        <td><?php echo $stats['low']; ?></td>
                                                        <td><?php echo $stats['out']; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Export Options -->
                            <div class="text-end">
                                <form method="POST" action="export_reports.php" class="d-inline">
                                    <input type="hidden" name="export_type" value="excel">
                                    <input type="hidden" name="start_date" value="<?php echo $start_date; ?>">
                                    <input type="hidden" name="end_date" value="<?php echo $end_date; ?>">
                                    <input type="hidden" name="ward" value="<?php echo $ward_filter; ?>">
                                    <button type="submit" class="btn btn-success me-2">
                                        <i class="fas fa-file-excel me-2"></i>Export to Excel
                                    </button>
                                </form>
                                <form method="POST" action="export_reports.php" class="d-inline">
                                    <input type="hidden" name="export_type" value="pdf">
                                    <input type="hidden" name="start_date" value="<?php echo $start_date; ?>">
                                    <input type="hidden" name="end_date" value="<?php echo $end_date; ?>">
                                    <input type="hidden" name="ward" value="<?php echo $ward_filter; ?>">
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-file-pdf me-2"></i>Export to PDF
                                    </button>
                                </form>
                            </div>
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