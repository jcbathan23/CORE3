<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['account_type'] !== 'admin') {
    header("Location: loginDefault.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="icon" href="logo.png">

    <!-- CSS Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.5.2/css/all.css" />
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../components/tm.css">

    <!-- Dark Mode Styles -->
    <style>
      /* Dark mode styles */
      body.dark-mode {
        background-color: #121212 !important;
        color: #ffffff !important;
      }
      
      /* Header styles */
      body.dark-mode .main-header {
        background-color: #1e1e1e !important;
        border-color: #333 !important;
      }
      
      body.dark-mode .main-header .navbar-brand,
      body.dark-mode .main-header .text-white,
      body.dark-mode .main-header a,
      body.dark-mode .main-header button {
        color: #ffffff !important;
      }
      
      /* Sidebar styles - Enhanced for complete coverage */
      body.dark-mode #sidebar {
        background-color: #1e1e1e !important;
        border-color: #333 !important;
      }
      
      /* Make ALL text in sidebar white */
      body.dark-mode #sidebar * {
        color: #ffffff !important;
      }
      
      body.dark-mode #sidebar .nav-link,
      body.dark-mode #sidebar .d-none.d-sm-inline,
      body.dark-mode #sidebar i.fs-4,
      body.dark-mode #sidebar span,
      body.dark-mode #sidebar a,
      body.dark-mode #sidebar .fs-5 {
        color: #ffffff !important;
      }
      
      body.dark-mode #sidebar .nav-link:hover {
        background-color: #333 !important;
      }
      
      /* Ensure all sidebar elements with background color are updated */
      body.dark-mode #sidebar .bg-side,
      body.dark-mode #sidebar [style*="background"],
      body.dark-mode #sidebar [style*="background-color"] {
        background-color: #1e1e1e !important;
      }
      
      /* Ensure submenu backgrounds are also dark */
      body.dark-mode #sidebar .collapse,
      body.dark-mode #sidebar .collapse .nav,
      body.dark-mode #sidebar .collapse .nav-link {
        background-color: #1e1e1e !important;
      }
      
      body.dark-mode #sidebar .collapse .nav-link:hover {
        background-color: #333 !important;
      }
      
      /* Card styles */
      body.dark-mode .card {
        background-color: #2d2d2d !important;
        border-color: #444 !important;
      }
      
      body.dark-mode .card-header {
        background-color: #2d2d2d !important;
        border-color: #444 !important;
      }
      
      body.dark-mode .card-footer {
        background-color: #333 !important;
        border-color: #444 !important;
      }
      
      /* Form control styles */
      body.dark-mode .form-control,
      body.dark-mode .form-select {
        background-color: #3d3d3d !important;
        border-color: #555 !important;
        color: #ffffff !important;
      }
      
      /* Text colors - Make sure ALL text is white */
      body.dark-mode {
        color: #ffffff !important;
      }
      
      body.dark-mode p,
      body.dark-mode span,
      body.dark-mode a,
      body.dark-mode div,
      body.dark-mode label,
      body.dark-mode input,
      body.dark-mode select,
      body.dark-mode textarea,
      body.dark-mode button,
      body.dark-mode li {
        color: #ffffff !important;
      }
      
      body.dark-mode .text-muted {
        color: #aaa !important;
      }
      
      body.dark-mode h1, 
      body.dark-mode h2, 
      body.dark-mode h3, 
      body.dark-mode h4, 
      body.dark-mode h5, 
      body.dark-mode h6 {
        color: #ffffff !important;
      }
      
      /* Additional elements */
      body.dark-mode .dropdown-menu {
        background-color: #2d2d2d !important;
        border-color: #444 !important;
      }
      
      body.dark-mode .dropdown-item:hover {
        background-color: #3d3d3d !important;
      }
      
      /* Override any inline styles */
      body.dark-mode [style*="color"] {
        color: #ffffff !important;
      }
    </style>
</head>
<body>
    <!-- Include the header and sidebar -->
    <?php include_once('index.php'); ?>
    
    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Dark Mode Script -->
    <script>
      // Apply dark mode if it's enabled in localStorage
      document.addEventListener('DOMContentLoaded', function() {
        if (localStorage.getItem('darkMode') === 'true') {
          document.body.classList.add('dark-mode');
        }
      });
    </script>
</body>
</html>