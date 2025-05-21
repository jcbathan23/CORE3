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

// Fetch all packages
$packages = [];
$query = "SELECT * FROM medical_packages ORDER BY price";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $packages[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Package List</title>
    <link rel="icon" href="logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../components/tm.css">
    <style>
        .package-card {
            transition: transform 0.3s ease-in-out;
        }
        .package-card:hover {
            transform: translateY(-5px);
        }
        .services-list {
            white-space: pre-line;
        }
    </style>
</head>
<body>
    <?php include_once 'index.php'; ?>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Medical Package List</h5>
                        <a href="patient_package.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-user-plus"></i> Assign Package
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (count($packages) > 0): ?>
                            <div class="row row-cols-1 row-cols-md-2 g-4 mb-4">
                                <?php foreach ($packages as $package): ?>
                                    <div class="col">
                                        <div class="card h-100 shadow-sm package-card">
                                            <div class="card-header bg-light">
                                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($package['package_name']); ?></h5>
                                            </div>
                                            <div class="card-body">
                                                <h6 class="card-subtitle mb-3 text-primary fw-bold">₱<?php echo number_format($package['price'], 2); ?></h6>
                                                <p class="card-text"><?php echo htmlspecialchars($package['description']); ?></p>
                                                <div class="mb-3">
                                                    <span class="badge bg-info rounded-pill">Duration: <?php echo $package['duration']; ?> days</span>
                                                </div>
                                                <h6 class="fw-bold">Services Included:</h6>
                                                <div class="services-list mb-3 ps-3 border-start border-success">
                                                    <?php echo nl2br(htmlspecialchars($package['services'])); ?>
                                                </div>
                                                <div class="text-end">
                                                    <a href="patient_package.php" class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-user-plus"></i> Assign to Patient
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="card-footer text-muted">
                                                <small>Created: <?php echo date('M d, Y', strtotime($package['created_at'])); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> No medical packages available yet. 
                                <a href="package_creation.php" class="alert-link">Create a new package</a>.
                            </div>
                        <?php endif; ?>
                        
                        <div class="text-end">
                            <a href="package_creation.php" class="btn btn-success">
                                <i class="fas fa-plus-circle"></i> Create New Package
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 