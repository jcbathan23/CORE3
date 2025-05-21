<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: loginDefault.php");
    exit();
}

// Include the appropriate file based on account type
if ($_SESSION['account_type'] === 'admin') {
    include_once('admin.php');
} elseif ($_SESSION['account_type'] === 'manager') {
    include_once('manager.php');
} else {
    include_once('user.php');
}

// Determine the current page
$current_page = basename($_SERVER['PHP_SELF']);

// Only include the dashboard if on the main index page
if ($current_page === 'index.php') {

// Placeholder PHP variables for dashboard data
// TODO: Populate these variables with actual data from your modules
$pharmacyTotalMedicines = 2500; // Example value
$pharmacyLowStockItems = 300;  // Example value
$pharmacyPendingOrders = 20;   // Example value

$homisTotalRevenue = 500000; // Example value
$homisPendingBills = 45;    // Example value
$homisTodaysSales = 25000;  // Example value

$prescriptionsTotal = 150; // Example value
$prescriptionsFilled = 100; // Example value
$prescriptionsPending = 30; // Example value

$inventoryTotalItems = 5000; // Example value
$inventoryLowStock = 150;   // Example value
$inventoryExpired = 50;    // Example value

// PHP variables for Pie Charts
// TODO: Populate these variables with actual data
$pharmacyInStock = $pharmacyTotalMedicines - $pharmacyLowStockItems; // Calculated example
$prescriptionsUnfilled = $prescriptionsTotal - $prescriptionsFilled - $prescriptionsPending; // Calculated example, ensure logic matches actual data

// Replace with actual database queries
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

// Pharmacy Statistics
$pharmacyQuery = "SELECT 
    COUNT(*) as total_medicines,
    SUM(CASE WHEN status = 'Low Stock' THEN 1 ELSE 0 END) as low_stock,
    (SELECT COUNT(*) FROM pharmacy_orders WHERE status = 'pending') as pending_orders
FROM pharmacy_inventory";
$pharmacyResult = $conn->query($pharmacyQuery);
if ($pharmacyResult && $row = $pharmacyResult->fetch_assoc()) {
    $pharmacyTotalMedicines = $row['total_medicines'];
    $pharmacyLowStockItems = $row['low_stock'];
    $pharmacyPendingOrders = $row['pending_orders'];
    $pharmacyInStock = $pharmacyTotalMedicines - $pharmacyLowStockItems;
}

// HOMIS Statistics
$homisQuery = "SELECT 
    SUM(CASE WHEN status = 'Paid' THEN amount ELSE 0 END) as total_revenue,
    COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pending_bills,
    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN amount ELSE 0 END) as today_sales
FROM homis_bills";
$homisResult = $conn->query($homisQuery);
if ($homisResult && $row = $homisResult->fetch_assoc()) {
    $homisTotalRevenue = $row['total_revenue'] ?? 0;
    $homisPendingBills = $row['pending_bills'] ?? 0;
    $homisTodaysSales = $row['today_sales'] ?? 0;
}

// Prescription Statistics
$prescriptionQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'filled' THEN 1 ELSE 0 END) as filled,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
FROM pharmacy_prescriptions";
$prescriptionResult = $conn->query($prescriptionQuery);
if ($prescriptionResult && $row = $prescriptionResult->fetch_assoc()) {
    $prescriptionsTotal = $row['total'];
    $prescriptionsFilled = $row['filled'];
    $prescriptionsPending = $row['pending'];
    $prescriptionsUnfilled = $prescriptionsTotal - $prescriptionsFilled - $prescriptionsPending;
}

// Inventory Statistics
$inventoryQuery = "SELECT 
    COUNT(*) as total_items,
    SUM(CASE WHEN status = 'Low Stock' THEN 1 ELSE 0 END) as low_stock,
    SUM(CASE WHEN expiry_date < CURDATE() THEN 1 ELSE 0 END) as expired
FROM homis_inventory";
$inventoryResult = $conn->query($inventoryQuery);
if ($inventoryResult && $row = $inventoryResult->fetch_assoc()) {
    $inventoryTotalItems = $row['total_items'];
    $inventoryLowStock = $row['low_stock'];
    $inventoryExpired = $row['expired'];
}

// Close database connection
$conn->close();

?>

<!-- DASHBOARD SECTION -->
<div class="dashboard">
  <div class="dashboard-header">
    <h2><i class="fas fa-hospital"></i><b> HOSPITAL DASHBOARD</b></h2>
    <p class="user-welcome">Welcome, <?php echo htmlspecialchars($_SESSION['email']); ?> (<?php echo htmlspecialchars($_SESSION['account_type']); ?>)</p>
  </div>
  <div class="dashboard-cards">
    <div class="card">
      <h3><i class="fas fa-pills"></i> Pharmacy Status</h3>
      <p>Total Medicines: <?php echo number_format($pharmacyTotalMedicines); ?></p>
      <p>Low Stock Items: <?php echo number_format($pharmacyLowStockItems); ?></p>
      <p>Pending Orders: <?php echo number_format($pharmacyPendingOrders); ?></p>
    </div>
    <div class="card">
      <h3><i class="fas fa-file-invoice-dollar"></i> HOMIS Billing</h3>
      <p>Total Revenue: ₱<?php echo number_format($homisTotalRevenue, 2); ?></p>
      <p>Pending Bills: <?php echo number_format($homisPendingBills); ?></p>
      <p>Today's Sales: ₱<?php echo number_format($homisTodaysSales, 2); ?></p>
    </div>
    <div class="card">
      <h3><i class="fas fa-prescription"></i> Prescriptions</h3>
      <p>Total: <?php echo number_format($prescriptionsTotal); ?></p>
      <p>Filled: <?php echo number_format($prescriptionsFilled); ?></p>
      <p>Pending: <?php echo number_format($prescriptionsPending); ?></p>
    </div>
    <div class="card">
      <h3><i class="fas fa-boxes"></i> Inventory</h3>
      <p>Total Items: <?php echo number_format($inventoryTotalItems); ?></p>
      <p>Low Stock: <?php echo number_format($inventoryLowStock); ?></p>
      <p>Expired: <?php echo number_format($inventoryExpired); ?></p>
    </div>
  </div>
  <div class="dashboard-chart">
    <canvas id="hospitalChart"></canvas>
  </div>
  <div class="dashboard-pie-charts" style="display: flex; justify-content: space-around; margin-top: 20px; flex-wrap: wrap;">
    <div class="pie-chart-container" style="width: 45%; min-width: 300px; height: 280px; background: white; padding: 15px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); margin-bottom: 20px;">
      <canvas id="pharmacyPieChart"></canvas>
    </div>
    <div class="pie-chart-container" style="width: 45%; min-width: 300px; height: 280px; background: white; padding: 15px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); margin-bottom: 20px;">
      <canvas id="prescriptionsPieChart"></canvas>
    </div>
  </div>
</div>

<?php
}
?>

<!-- Font Awesome for burger icon -->
<link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
<!-- Google Fonts -->
<link href="assets/vendor/fonts/opensans/opensans.css" rel="stylesheet">
<!-- Bootstrap CSS -->
<link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

<style>
  body {
    font-family: 'Open Sans', sans-serif;
    background-color: #F0F4F8; /* Light blue-gray background for the whole page */
  }
  #sidebar {
    width: 300px;
    min-width: 300px;
    max-width: 300px;
    transition: left 0.3s, margin-left 0.3s;
    background: #4A628A;
    position: fixed;
    top: 60px;
    left: 0;
    height: 100vh;
    z-index: 1040;
    margin-left: 0;
    box-shadow: 2px 0 8px rgba(0,0,0,0.1);
  }
  #sidebar.collapsed {
    left: -300px;
  }
  #sidebar-backdrop {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.3);
    z-index: 1039;
  }
  #sidebar.show-backdrop + #sidebar-backdrop {
    display: block;
  }
  #sidebarToggle {
    display: inline-block;
    z-index: 1100;
  }
  .main-header {
    width: 100vw;
    min-height: 64px;
    height: 64px;
    background: #4A628A;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 2000;
  }
  .main-header .navbar-brand {
    font-size: 1.5rem;
    color: #fff;
    font-weight: bold;
    margin-left: 12px;
  }
  .main-header .profile-dropdown {
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .main-header .profile-dropdown img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #fff;
  }
  .main-header .dropdown-menu {
    right: 0;
    left: auto;
    min-width: 180px;
  }
  .main-content {
    margin-left: 0;
    transition: margin-left 0.3s;
    padding-top: 64px;
  }
  /* Always shift main content when sidebar is open */
  #sidebar:not(.collapsed) ~ .main-content {
    margin-left: 100px;
  }
  .dashboard {
    margin-top: 100px;
    padding: 20px;
    background-color: #FFFFFF; /* Changed from rgb(243, 243, 243) to white for a cleaner look with new body bg */
    border-radius: 15px; /* Slightly more rounded corners */
    box-shadow: 0 8px 16px rgba(0,0,0,0.05); /* Softer shadow */
    transition: margin-left 0.3s;
  }
  
  #sidebar.collapsed ~ .dashboard {
    margin-left: 0;
  }

  .dashboard-header {
    text-align: center;
    margin-bottom: 20px;
    color: #3A4F6A; /* Darker shade of sidebar blue */
  }
  
  .user-welcome {
    color: #5B739A; /* Muted shade of sidebar blue */
    font-size: 1.1em;
    margin-top: 10px;
  }
  
  .dashboard-cards {
    display: flex;
    justify-content: space-around;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 30px;
  }
  
  .card {
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 6px 12px rgba(0,0,0,0.08); /* Adjusted shadow */
    padding: 20px;
    flex: 1 1 250px;
    text-align: center;
    transition: transform 0.3s, box-shadow 0.3s;
  }
  
  .card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.12); /* Enhanced hover shadow */
  }
  
  .card h3 {
    color: #4A628A; /* Sidebar blue */
    margin-bottom: 15px;
    font-size: 1.2em;
    font-weight: 600;
  }
  
  .card p {
    margin: 8px 0;
    color: #555F6B; /* Darker gray for better readability */
  }
  
  .dashboard-chart {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    height: 300px;
  }

  @media (max-width: 768px) {
    .dashboard {
      margin-left: 0;
      padding: 10px;
    }
    
    .dashboard-cards {
      flex-direction: column;
    }
    
    .card {
      margin: 10px 0;
    }
  }

  #sidebar ul.nav {
    width: 100%;
    padding: 0;
    margin: 0;
  }

  #sidebar ul.nav li {
    width: 100%;
    margin: 0;
    padding: 0;
  }

  #sidebar ul.nav li a {
    width: 100%;
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
  }

  #sidebar ul.nav li a i {
    width: 20px;
    text-align: center;
    margin-right: 10px;
  }

  #sidebar ul.nav li a span {
    flex: 1;
  }

  #sidebar .collapse ul {
    width: 100%;
    padding-left: 0;
    margin: 0;
  }

  #sidebar .collapse ul li {
    width: 100%;
    margin: 0;
    padding: 0;
  }

  #sidebar .collapse ul li a {
    width: 100%;
    padding: 0.5rem 1rem 0.5rem 2.5rem;
  }
</style>

<!-- HEADER WITH BURGER MENU AND PROFILE -->
<div class="main-header">
  <div style="display: flex; align-items: center;">
    <button class="btn btn-outline-secondary me-2" id="sidebarToggle" type="button">
      <i class="fas fa-bars"></i>
    </button>
    <span class="navbar-brand mb-0 h1"><b>HOSPITAL MANAGEMENT SYSTEM</b></span>
  </div>
  <div class="profile-dropdown dropdown">
    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
      <img src="logo.png" alt="profile pic">
      <span class="d-none d-sm-inline mx-1"><?php echo htmlspecialchars($_SESSION['account_type']); ?></span>
    </a>
    <ul class="dropdown-menu dropdown-bg text-small shadow" aria-labelledby="dropdownUser">
      <li><a href="settings.php" class="dropdown-item">Setting</a></li>
      <li><a href="profile.php" class="dropdown-item">Profile</a></li>
      <li><a href="#" class="dropdown-item">Help & Support</a></li>
      <li><a href="logout.php" class="dropdown-item">Logout</a></li>
    </ul>
  </div>
</div>

<!-- SIDEBAR -->
<div id="sidebar" class="center bg-side">
  <div class="d-flex flex-column align-items-sm-start px-3 pt-2 text-white min-vh-100" style="background: #4A628A;">
    <a href="index.php" class="d-flex text-decoration-none align-items-center mb-md-0 p-1 text-white text-decoration-none">
      <img src="logo.png" width="65" height="65" alt="">
      <span class="fs-5 d-none d-sm-inline fw-bold" style="color: white;">CORE3</span>
    </a>
    <ul class="nav nav-link flex-column mb-sm-auto mb-0 align-items-center align-items-sm-start w-100" id="menu" style="color: white;">
      <!-- Modules -->
      <li class="nav-item w-100">
        <a href="#submenu" data-bs-toggle="collapse" class="nav-link align-middle p-2">
          <i class="fa-solid fa-mattress-pillow" style="color: white;"></i> 
          <span class="ms-1 d-none d-sm-inline" style="color: white;">BEN & LINEN</span>
        </a>
        <ul class="collapse nav flex-column ms-1 px-3 w-100" id="submenu" data-bs-parent="#menu">
          <li class="w-100">
            <a href="bed_allocation.php" class="nav-link p-2 rounded-1">
              <span class="d-none d-sm-inline" style="color: white;">• BED ALLOCATION & AVAILABILITY</span>
            </a>
          </li>
          <li class="w-100">
            <a href="patient_bed_assignment.php" class="nav-link p-2 rounded-1">
              <span class="d-none d-sm-inline" style="color: white;">• PATIENT BED ASSIGMENT & TRANSFER</span>
            </a>
          </li>
          <li class="w-100">
            <a href="linen_inventory.php" class="nav-link p-2 rounded-1">
              <span class="d-none d-sm-inline" style="color: white;">• LINEN INVENTORY & TRACKING</span>
            </a>
          </li>
          <li class="w-100">
            <a href="linen_laundry.php" class="nav-link p-2 rounded-1">
              <span class="d-none d-sm-inline" style="color: white;">• LINEN LAUNDRY & REPLACEMENT</span>
            </a>
          </li>
          <li class="w-100">
            <a href="linen_report.php" class="nav-link p-2 rounded-1">
              <span class="d-none d-sm-inline" style="color: white;">• REPORT</span>
            </a>
          </li>
        </ul>
      </li>
      <li class="nav-item w-100">
        <a href="#submenu1" data-bs-toggle="collapse" class="nav-link align-middle p-2">
          <i class="fa-solid fa-chart-simple" style="color: white;"></i> 
          <span class="ms-1 d-none d-sm-inline" style="color: white;">HOMIS</span>
        </a>
        <ul class="collapse nav flex-column ms-1 px-3 w-100" id="submenu1" data-bs-parent="#menu">
          <li class="w-100">
            <a href="homis_reports.php" class="nav-link p-2 rounded-1">
              <span class="d-none d-sm-inline" style="color: white;">• HOMIS REPORTS</span>
            </a>
          </li>
          <li class="w-100">
            <a href="homis_inventory.php" class="nav-link p-2 rounded-1">
              <span class="d-none d-sm-inline" style="color: white;">• HOMIS INVENTORY</span>
            </a>
          </li>
          <li class="w-100">
            <a href="homis_billing.php" class="nav-link p-2 rounded-1">
              <span class="d-none d-sm-inline" style="color: white;">• HOMIS BILLING</span>
            </a>
          </li>
        </ul>
      </li>
      <li class="nav-item w-100">
        <a href="#submenu2" data-bs-toggle="collapse" class="nav-link align-middle p-2">
          <i class="fa-solid fa-prescription-bottle-medical" style="color: white;"></i> 
          <span class="ms-1 d-none d-sm-inline" style="color: white;">PHARMACY</span>
        </a>
        <ul class="collapse nav flex-column ms-1 px-3 w-100" id="submenu2" data-bs-parent="#menu">
          <li class="w-100">
            <a href="pharmacy_inventory.php" class="nav-link p-2 rounded-1">
              <span class="d-none d-sm-inline" style="color: white;">• PHARMACY INVENTORY</span>
            </a>
          </li>
          <li class="w-100">
            <a href="pharmacy_prescriptions.php" class="nav-link p-2 rounded-1">
              <span class="d-none d-sm-inline" style="color: white;">• PHARMACY PRESCRIPTIONS</span>
            </a>
          </li>
          <li class="w-100">
            <a href="pharmacy_reports.php" class="nav-link p-2 rounded-1">
              <span class="d-none d-sm-inline" style="color: white;">• PHARMACY REPORTS</span>
            </a>
          </li>
          <li class="w-100">
            <a href="pharmacy_orders.php" class="nav-link p-2 rounded-1">
              <span class="d-none d-sm-inline" style="color: white;">• PHARMACY ORDERS</span>
            </a>
          </li>
          <li class="w-100">
            <a href="pharmacy_suppliers.php" class="nav-link p-2 rounded-1">
              <span class="d-none d-sm-inline" style="color: white;">• PHARMACY SUPPLIERS</span>
            </a>
          </li>
        </ul>
      </li>
      <li class="nav-item w-100">
        <a href="#submenu3" data-bs-toggle="collapse" class="nav-link align-middle p-2">
          <i class="fa-solid fa-notes-medical" style="color: white;"></i> 
          <span class="ms-1 d-none d-sm-inline" style="color: white;">MEDICAL PACKAGE</span>
        </a>
        <ul class="collapse nav flex-column ms-1 px-3 w-100" id="submenu3" data-bs-parent="#menu">
          <li class="w-100">
            <a href="package_creation.php" class="nav-link p-2 rounded-1">
              <span class="d-none d-sm-inline" style="color: white;">• PACKAGE CREATION</span>
            </a>
          </li>
          <li class="w-100">
            <a href="patient_package.php" class="nav-link p-2 rounded-1">
              <span class="d-none d-sm-inline" style="color: white;">• PATIENT PACKAGE</span>
            </a>
          </li>
          <li class="w-100">
            <a href="service_tracking.php" class="nav-link p-2 rounded-1">
              <span class="d-none d-sm-inline" style="color: white;">• SERVICE UTILIZATION TRACKING</span>
            </a>
          </li>
          <li class="w-100">
            <a href="package_billing.php" class="nav-link p-2 rounded-1">
              <span class="d-none d-sm-inline" style="color: white;">• BILLING & PAYMENT</span>
            </a>
          </li>
          <li class="w-100">
            <a href="package_report.php" class="nav-link p-2 rounded-1">
              <span class="d-none d-sm-inline" style="color: white;">• REPORT</span>
            </a>
          </li>
        </ul>
      </li>
      <li class="nav-item w-100">
        <a href="#submenu4" data-bs-toggle="collapse" class="nav-link align-middle p-2">
          <i class="fa-solid fa-bed" style="color: white;"></i> 
          <span class="ms-1 d-none d-sm-inline" style="color: white;">WARD</span>
        </a>
        <ul class="collapse nav flex-column ms-1 px-3 w-100" id="submenu4" data-bs-parent="#menu">
          <li class="w-100">
            <a href="bed_room_allocation.php" class="nav-link p-2 rounded-1">
              <span class="d-none d-sm-inline" style="color: white;">• BED & ROOM ALLOCATION</span>
            </a>
          </li>
          <li class="w-100">
            <a href="patient_monitoring.php" class="nav-link p-2 rounded-1">
              <span class="d-none d-sm-inline" style="color: white;">• PATIENT MONITORING</span>
            </a>
          </li>
          <li class="w-100">
            <a href="nurse_assignment.php" class="nav-link p-2 rounded-1">
              <span class="d-none d-sm-inline" style="color: white;">• NURSE ASSIGNMENT</span>
            </a>
          </li>
          <li class="w-100">
            <a href="ward_supplies.php" class="nav-link p-2 rounded-1">
              <span class="d-none d-sm-inline" style="color: white;">• LINEN & WARD SUPPLIES</span>
            </a>
          </li>
          <li class="w-100">
            <a href="ward_report.php" class="nav-link p-2 rounded-1">
              <span class="d-none d-sm-inline" style="color: white;">• REPORT</span>
            </a>
          </li>
        </ul>
      </li>
    </ul>
    <hr>
  </div>
</div>
<div id="sidebar-backdrop"></div>

<div class="main-content">
  <div class="container-fluid">
    <div class="row flex-nowrap">
      <div class="col py-3">
        <!-- Main content here -->
      </div>
    </div>
  </div>
</div>

<!-- Include Chart.js -->
<script src="assets/vendor/chartjs/Chart.min.js"></script>
<!-- Bootstrap Bundle JS (includes Popper for dropdowns, modals, etc.) -->
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

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

  // Adjust dashboard size on sidebar toggle
  function adjustDashboard() {
    var dashboard = document.querySelector('.dashboard');
    if (sidebar.classList.contains('collapsed')) {
      dashboard.style.paddingLeft = '20px';
    } else {
      dashboard.style.paddingLeft = '320px'; // Adjust based on sidebar width
    }
  }

  sidebarToggle.addEventListener('click', function() {
    setTimeout(function() {
      adjustDashboard();
      hospitalChart.resize();
    }, 300);
  });

  // Initial adjustment
  adjustDashboard();
});

// Initialize Chart.js with updated data
var ctx = document.getElementById('hospitalChart').getContext('2d');
var hospitalChart = new Chart(ctx, {
  type: 'bar',
  data: {
    labels: ['Pharmacy Sales', 'Prescriptions', 'Inventory Items', 'Billing Revenue'],
    datasets: [{
      label: 'Monthly Statistics',
      data: [
        <?php echo $homisTodaysSales; ?>, 
        <?php echo $prescriptionsTotal; ?>, 
        <?php echo $inventoryTotalItems; ?>, 
        <?php echo $homisTotalRevenue; ?>
      ],
      backgroundColor: [
        'rgba(74, 98, 138, 0.7)',  // #4A628A with alpha
        'rgba(91, 115, 153, 0.7)', // Lighter shade
        'rgba(122, 141, 173, 0.7)',// Even lighter shade
        'rgba(158, 172, 199, 0.7)' // Lightest shade
      ],
      borderColor: [
        'rgba(74, 98, 138, 1)',
        'rgba(91, 115, 153, 1)',
        'rgba(122, 141, 173, 1)',
        'rgba(158, 172, 199, 1)'
      ],
      borderWidth: 1
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          callback: function(value) {
            if (value >= 1000) {
              return '₱' + value.toLocaleString();
            }
            return value;
          }
        }
      }
    },
    plugins: {
      legend: {
        position: 'top',
      },
      title: {
        display: true,
        text: 'Monthly Hospital Operations Overview'
      }
    }
  }
});

// Adjust chart size on sidebar toggle
sidebarToggle.addEventListener('click', function() {
  setTimeout(function() {
    hospitalChart.resize();
    if (typeof pharmacyPieChart !== 'undefined') pharmacyPieChart.resize();
    if (typeof prescriptionsPieChart !== 'undefined') prescriptionsPieChart.resize();
  }, 300);
});

// Initialize Pharmacy Pie Chart
var pharmacyPieCtx = document.getElementById('pharmacyPieChart').getContext('2d');
var pharmacyPieChart = new Chart(pharmacyPieCtx, {
  type: 'pie',
  data: {
    labels: ['In Stock', 'Low Stock', 'Pending Orders'],
    datasets: [{
      label: 'Pharmacy Stock Status',
      data: [
        <?php echo $pharmacyInStock; ?>,
        <?php echo $pharmacyLowStockItems; ?>,
        <?php echo $pharmacyPendingOrders; ?>
      ],
      backgroundColor: [
        'rgba(74, 98, 138, 0.8)',  // #4A628A primary
        'rgba(255, 159, 64, 0.8)', // Orange - for contrast on low stock/pending
        'rgba(255, 99, 132, 0.8)'  // Red - for contrast on pending orders
      ],
      borderColor: [
        'rgba(74, 98, 138, 1)',
        'rgba(255, 159, 64, 1)',
        'rgba(255, 99, 132, 1)'
      ],
      borderWidth: 1
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'top',
      },
      title: {
        display: true,
        text: 'Pharmacy Stock Status'
      }
    }
  }
});

// Initialize Prescriptions Pie Chart
var prescriptionsPieCtx = document.getElementById('prescriptionsPieChart').getContext('2d');
var prescriptionsPieChart = new Chart(prescriptionsPieCtx, {
  type: 'pie',
  data: {
    labels: ['Filled', 'Pending', 'Unfilled'], // Added 'Unfilled' based on new variable
    datasets: [{
      label: 'Prescription Status',
      data: [
        <?php echo $prescriptionsFilled; ?>,
        <?php echo $prescriptionsPending; ?>,
        <?php echo $prescriptionsUnfilled; ?> // Make sure this variable is populated correctly
      ],
      backgroundColor: [
        'rgba(74, 98, 138, 0.8)',  // #4A628A primary for 'Filled'
        'rgba(255, 205, 86, 0.8)', // Yellow - for 'Pending'
        'rgba(122, 141, 173, 0.8)' // Lighter shade of primary for 'Unfilled'
      ],
      borderColor: [
        'rgba(74, 98, 138, 1)',
        'rgba(255, 205, 86, 1)',
        'rgba(122, 141, 173, 1)'
      ],
      borderWidth: 1
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'top',
      },
      title: {
        display: true,
        text: 'Prescription Status'
      }
    }
  }
});
</script>