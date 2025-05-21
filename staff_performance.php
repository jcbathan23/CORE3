<?php
$title = 'Staff Performance';
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
    <h2 class="fw-bold" style="color: #4A628A;">Staff Performance</h2>
    <p class="text-muted">Evaluate and manage staff performance.</p>
    
    <!-- Operations Section -->
    <div class="mb-4">
      <button class="btn btn-primary" style="background-color: #4A628A; border-color: #4A628A;">Evaluate Performance</button>
    </div>

    <!-- Table Section -->
    <div class="card shadow-sm">
      <div class="card-body">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Staff ID</th>
              <th>Name</th>
              <th>Role</th>
              <th>Performance Score</th>
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