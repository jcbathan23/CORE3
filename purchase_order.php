<?php
$title = 'Purchase Order';
include_once('admin.php');
?>

<style>
  .main-content {
    transition: margin-left 0.3s;
  }
  #sidebar:not(.collapsed) ~ .main-content {
    margin-left: 300px;
    width: calc(100% - 300px);
  }
  #sidebar.collapsed ~ .main-content {
    margin-left: 100px;
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

<div class="main-content" style="margin-top: -100px;">
  <div class="container-fluid py-4">
    <h2 class="fw-bold" style="color: #4A628A;">Purchase Order</h2>
    <p class="text-muted">Manage and track purchase orders.</p>
    
    <!-- Operations Section -->
    <div class="mb-4">
      <button class="btn btn-primary" style="background-color: #4A628A; border-color: #4A628A;">Create Order</button>
      <button class="btn btn-secondary">View Orders</button>
    </div>

    <!-- Table Section -->
    <div class="card shadow-sm">
      <div class="card-body">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Supplier</th>
              <th>Order Date</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <!-- Dynamic rows go here -->
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div> 