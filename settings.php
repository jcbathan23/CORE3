<?php
$title = 'Settings';
include_once('admin.php');
?>

<div class="main-content">
  <div class="container-fluid py-4">
    <div class="row">
      <div class="col-12 mb-4">
        <h2 class="fw-bold" style="color: #4A628A; margin-top: -100px;">Settings</h2>
        <p class="text-muted">Manage your account and system settings</p>
      </div>
    </div>

    <div class="row" style="margin-top: -30px;">
      <div class="col-12">
        <div class="card shadow-sm">
          <div class="card-header bg-white">
            <ul class="nav nav-tabs card-header-tabs" id="settingsTabs" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active" id="account-tab" data-bs-toggle="tab" data-bs-target="#account" type="button" role="tab" aria-controls="account" aria-selected="true">
                  <i class="fas fa-user-cog me-2"></i>Account
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab" aria-controls="security" aria-selected="false">
                  <i class="fas fa-shield-alt me-2"></i>Security
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications" type="button" role="tab" aria-controls="notifications" aria-selected="false">
                  <i class="fas fa-bell me-2"></i>Notifications
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="appearance-tab" data-bs-toggle="tab" data-bs-target="#appearance" type="button" role="tab" aria-controls="appearance" aria-selected="false">
                  <i class="fas fa-palette me-2"></i>Appearance
                </button>
              </li>
            </ul>
          </div>
          <div class="card-body">
            <div class="tab-content" id="settingsTabsContent">
              <!-- Account Settings Tab -->
              <div class="tab-pane fade show active" id="account" role="tabpanel" aria-labelledby="account-tab">
                <!-- Account settings content (unchanged) -->
                <form>
                  <h5 class="mb-3">Account Settings</h5>
                  <div class="mb-3">
                    <label for="language" class="form-label">Language</label>
                    <select class="form-select" id="language">
                      <option selected>English</option>
                      <option>Filipino</option>
                      <option>Spanish</option>
                      <option>French</option>
                    </select>
                  </div>
                  <!-- Other account settings (unchanged) -->
                  <div class="d-flex justify-content-end">
                    <button type="button" class="btn btn-secondary me-2">Reset to Default</button>
                    <button type="submit" class="btn btn-primary" style="background-color: #4A628A; border-color: #4A628A;">Save Changes</button>
                  </div>
                </form>
              </div>

              <!-- Security Settings Tab -->
              <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                <!-- Security settings content (unchanged) -->
              </div>

              <!-- Notifications Settings Tab -->
              <div class="tab-pane fade" id="notifications" role="tabpanel" aria-labelledby="notifications-tab">
                <!-- Notifications settings content (unchanged) -->
              </div>
              
              <!-- Appearance Settings Tab -->
              <div class="tab-pane fade" id="appearance" role="tabpanel" aria-labelledby="appearance-tab">
                <h5 class="mb-3">Theme</h5>
                <div class="mb-4">
                  <div class="form-check form-switch d-flex align-items-center">
                    <input class="form-check-input me-2" type="checkbox" id="darkModeToggle">
                    <label class="form-check-label" for="darkModeToggle">Dark Mode</label>
                  </div>
                  <small class="text-muted d-block mt-2">Toggle dark mode to change the appearance of the application.</small>
                </div>
                
                <h5 class="mb-3">Font Size</h5>
                <div class="mb-4">
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="fontSizeOptions" id="smallFont" value="small">
                    <label class="form-check-label" for="smallFont">Small</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="fontSizeOptions" id="mediumFont" value="medium" checked>
                    <label class="form-check-label" for="mediumFont">Medium</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="fontSizeOptions" id="largeFont" value="large">
                    <label class="form-check-label" for="largeFont">Large</label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Add this style for dark mode -->
<style id="darkModeStyles">
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
  body.dark-mode .main-header .text-white {
    color: #ffffff !important;
  }
  
  /* Sidebar styles */
  body.dark-mode #sidebar {
    background-color: #1e1e1e !important;
  }
  
  body.dark-mode #sidebar .nav-link,
  body.dark-mode #sidebar .d-none.d-sm-inline,
  body.dark-mode #sidebar i.fs-4 {
    color: #ffffff !important;
  }
  
  body.dark-mode #sidebar .nav-link:hover {
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
  
  body.dark-mode .form-control:focus,
  body.dark-mode .form-select:focus {
    background-color: #3d3d3d !important;
    border-color: #666 !important;
    color: #ffffff !important;
  }
  
  body.dark-mode .form-control:disabled,
  body.dark-mode .form-select:disabled,
  body.dark-mode .form-control[readonly] {
    background-color: #333 !important;
  }
  
  /* Table styles */
  body.dark-mode .table {
    color: #ffffff !important;
  }
  
  body.dark-mode .table-hover tbody tr:hover {
    background-color: #3d3d3d !important;
  }
  
  /* Nav tabs styles */
  body.dark-mode .nav-tabs {
    border-color: #444 !important;
  }
  
  body.dark-mode .nav-tabs .nav-link {
    color: #ddd !important;
  }
  
  body.dark-mode .nav-tabs .nav-link.active {
    background-color: #3d3d3d !important;
    color: #fff !important;
    border-color: #555 #555 #3d3d3d !important;
  }
  
  /* Text colors */
  body.dark-mode .text-muted {
    color: #aaa !important;
  }
  
  body.dark-mode h1, 
  body.dark-mode h2, 
  body.dark-mode h3, 
  body.dark-mode h4, 
  body.dark-mode h5, 
  body.dark-mode h6,
  body.dark-mode .card-title {
    color: #ffffff !important;
  }
  
  /* Background colors */
  body.dark-mode .bg-white {
    background-color: #2d2d2d !important;
  }
  
  body.dark-mode .bg-light {
    background-color: #333 !important;
  }
  
  /* List group styles */
  body.dark-mode .list-group-item {
    background-color: #2d2d2d !important;
    border-color: #444 !important;
    color: #ffffff !important;
  }
  
  /* Modal styles */
  body.dark-mode .modal-content {
    background-color: #2d2d2d !important;
    border-color: #444 !important;
  }
  
  body.dark-mode .modal-header,
  body.dark-mode .modal-footer {
    border-color: #444 !important;
  }
  
  /* Button styles */
  body.dark-mode .btn-close {
    filter: invert(1) grayscale(100%) brightness(200%);
  }
  
  /* Dropdown styles */
  body.dark-mode .dropdown-menu {
    background-color: #2d2d2d !important;
    border-color: #444 !important;
  }
  
  body.dark-mode .dropdown-item {
    color: #ffffff !important;
  }
  
  body.dark-mode .dropdown-item:hover {
    background-color: #3d3d3d !important;
  }
</style>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    
    // Check if dark mode is saved in localStorage
    const isDarkMode = localStorage.getItem('darkMode') === 'true';
    
    // Set initial state based on saved preference
    if (isDarkMode) {
      document.body.classList.add('dark-mode');
      darkModeToggle.checked = true;
    }
    
    // Toggle dark mode immediately when the switch is clicked
    darkModeToggle.addEventListener('change', function() {
      if (this.checked) {
        enableDarkMode();
      } else {
        disableDarkMode();
      }
    });
    
    // Helper functions
    function enableDarkMode() {
      document.body.classList.add('dark-mode');
      localStorage.setItem('darkMode', 'true');
    }
    
    function disableDarkMode() {
      document.body.classList.remove('dark-mode');
      localStorage.setItem('darkMode', 'false');
    }
  });
</script>

<style>
  .main-content {
    transition: margin-left 0.3s;
  }
  #sidebar:not(.collapsed) ~ .main-content {
    margin-left: 300px;
    width: calc(100% - 300px);
  }
  #sidebar.collapsed ~ .main-content {
    margin-left: 50px;
    width: calc(100% - 100px);
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var sidebar = document.getElementById('sidebar');
  var sidebarToggle = document.getElementById('sidebarToggle');

  function adjustContent() {
    var mainContent = document.querySelector('.main-content');
    if (sidebar.classList.contains('collapsed')) {
      mainContent.style.marginLeft = '100px';
      mainContent.style.width = 'calc(100% - 100px)';
    } else {
      mainContent.style.marginLeft = '300px';
      mainContent.style.width = 'calc(100% - 300px)';
    }
  }

  sidebarToggle.addEventListener('click', function() {
    setTimeout(function() {
      adjustContent();
    }, 300);
  });

  // Initial adjustment
  adjustContent();
});
</script>

<style>
  .form-check {
    justify-content: flex-start;
  }
  .tab-pane{
    text-align: left;
  }
</style>