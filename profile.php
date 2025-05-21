<?php
$title = 'Profile';
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

<div class="main-content">
  <div class="container-fluid py-4">
    <div class="row">
      <div class="col-12 ">
        <h2 class="fw-bold" style="color: #4A628A; margin-top: -100px;">My Profile</h2>
        <p class="text-muted">View and manage your profile information</p>
      </div>
    </div>

    <div class="row">
      <!-- Profile Information Card -->
      <div class="col-lg-4 mb-4">
        <div class="card shadow-sm">
          <div class="card-body text-center">
            <div class="position-relative mx-auto mb-3" style="width: 150px;">
              <img src="logo.png" alt="Profile Picture" class="rounded-circle img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
              <button class="btn btn-sm btn-primary position-absolute bottom-0 end-0 rounded-circle" style="width: 32px; height: 32px; background-color: #4A628A; border-color: #4A628A;" data-bs-toggle="modal" data-bs-target="#changePhotoModal">
                <i class="fas fa-camera"></i>
              </button>
            </div>
            <h4 class="card-title mb-1"></h4>
            <p class="text-muted mb-2">Administrator</p>
            <p class="text-muted mb-3">
              <i class="fas fa-envelope me-2"></i>admin@example.com
            </p>
            <div class="d-grid gap-2">
              <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal" style="color: #4A628A; border-color: #4A628A;">
                <i class="fas fa-edit me-2"></i>Edit Profile
              </button>
            </div>
          </div>
          <div class="card-footer bg-light">
            <div class="d-flex justify-content-between align-items-center">
              <small class="text-muted">Member since: Jan 2023</small>
              <span class="badge bg-success">Active</span>
            </div>
          </div>
        </div>
        
        <!-- Contact Information Card -->
        <div class="card shadow-sm mt-4">
          <div class="card-header bg-white">
            <h5 class="card-title mb-0">Contact Information</h5>
          </div>
          <div class="card-body">
            <ul class="list-group list-group-flush">
              <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                <div>
                  <i class="fas fa-phone me-2 text-muted"></i>
                  <span>Phone</span>
                </div>
                <span>+63 912 345 6789</span>
              </li>
              <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                <div>
                  <i class="fas fa-envelope me-2 text-muted"></i>
                  <span>Email</span>
                </div>
                <span>admin@example.com</span>
              </li>
              <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                <div>
                  <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                  <span>Location</span>
                </div>
                <span>Manila, Philippines</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Profile Details Card -->
      <div class="col-lg-8">
        <div class="card shadow-sm mb-4">
          <div class="card-header bg-white">
            <h5 class="card-title mb-0">Personal Information</h5>
          </div>
          <div class="card-body">
            <form>
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="firstName" class="form-label">First Name</label>
                  <input type="text" class="form-control" id="firstName" value="" readonly>
                </div>
                <div class="col-md-6">
                  <label for="lastName" class="form-label">Last Name</label>
                  <input type="text" class="form-control" id="lastName" value="" readonly>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="email" class="form-label">Email Address</label>
                  <input type="email" class="form-control" id="email" value="admin@example.com" readonly>
                </div>
                <div class="col-md-6">
                  <label for="phone" class="form-label">Phone Number</label>
                  <input type="tel" class="form-control" id="phone" value="+63 912 345 6789" readonly>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="birthdate" class="form-label">Date of Birth</label>
                  <input type="text" class="form-control" id="birthdate" value="January 15, 1985" readonly>
                </div>
                <div class="col-md-6">
                  <label for="gender" class="form-label">Gender</label>
                  <input type="text" class="form-control" id="gender" value="Male" readonly>
                </div>
              </div>
              <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <input type="text" class="form-control" id="address" value="123 Main Street, Makati City, Metro Manila" readonly>
              </div>
            </form>
          </div>
        </div>
        
        <!-- Work Information Card -->
        <div class="card shadow-sm mb-4">
          <div class="card-header bg-white">
            <h5 class="card-title mb-0">Work Information</h5>
          </div>
          <div class="card-body">
            <form>
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="department" class="form-label">Department</label>
                  <input type="text" class="form-control" id="department" value="Administration" readonly>
                </div>
                <div class="col-md-6">
                  <label for="position" class="form-label">Position</label>
                  <input type="text" class="form-control" id="position" value="System Administrator" readonly>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="employeeId" class="form-label">Employee ID</label>
                  <input type="text" class="form-control" id="employeeId" value="EMP-2023-001" readonly>
                </div>
                <div class="col-md-6">
                  <label for="joinDate" class="form-label">Join Date</label>
                  <input type="text" class="form-control" id="joinDate" value="January 10, 2023" readonly>
                </div>
              </div>
              <div class="mb-3">
                <label for="bio" class="form-label">Bio</label>
                <textarea class="form-control" id="bio" rows="3" readonly>Hospital administrator with over 5 years of experience in healthcare management. Specializing in system administration and process optimization.</textarea>
              </div>
            </form>
          </div>
        </div>
        
        <!-- Skills & Qualifications Card -->
        <div class="card shadow-sm">
          <div class="card-header bg-white">
            <h5 class="card-title mb-0">Skills & Qualifications</h5>
          </div>
          <div class="card-body">
            <h6 class="mb-2">Skills</h6>
            <div class="mb-4">
              <span class="badge rounded-pill bg-primary me-2 mb-2" style="background-color: #4A628A !important;">Hospital Management</span>
              <span class="badge rounded-pill bg-primary me-2 mb-2" style="background-color: #4A628A !important;">System Administration</span>
              <span class="badge rounded-pill bg-primary me-2 mb-2" style="background-color: #4A628A !important;">Healthcare IT</span>
              <span class="badge rounded-pill bg-primary me-2 mb-2" style="background-color: #4A628A !important;">Staff Training</span>
              <span class="badge rounded-pill bg-primary me-2 mb-2" style="background-color: #4A628A !important;">Process Optimization</span>
              <span class="badge rounded-pill bg-primary me-2 mb-2" style="background-color: #4A628A !important;">Data Analysis</span>
            </div>
            
            <h6 class="mb-2">Education</h6>
            <div class="mb-4">
              <div class="d-flex justify-content-between mb-1">
                <strong>Bachelor of Science in Healthcare Administration</strong>
                <span>2010 - 2014</span>
              </div>
              <div>University of the Philippines</div>
            </div>
            
            <h6 class="mb-2">Certifications</h6>
            <div>
              <div class="d-flex justify-content-between mb-1">
                <strong>Certified Healthcare Information Systems Professional</strong>
                <span>2018</span>
              </div>
              <div class="d-flex justify-content-between mb-1">
                <strong>Hospital Management Certification</strong>
                <span>2016</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form>
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="editFirstName" class="form-label">First Name</label>
              <input type="text" class="form-control" id="editFirstName" value="John">
            </div>
            <div class="col-md-6">
              <label for="editLastName" class="form-label">Last Name</label>
              <input type="text" class="form-control" id="editLastName" value="Doe">
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="editEmail" class="form-label">Email Address</label>
              <input type="email" class="form-control" id="editEmail" value="john.doe@example.com">
            </div>
            <div class="col-md-6">
              <label for="editPhone" class="form-label">Phone Number</label>
              <input type="tel" class="form-control" id="editPhone" value="+63 912 345 6789">
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="editBirthdate" class="form-label">Date of Birth</label>
              <input type="date" class="form-control" id="editBirthdate" value="1985-01-15">
            </div>
            <div class="col-md-6">
              <label for="editGender" class="form-label">Gender</label>
              <select class="form-select" id="editGender">
                <option value="Male" selected>Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
                <option value="Prefer not to say">Prefer not to say</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label for="editAddress" class="form-label">Address</label>
            <input type="text" class="form-control" id="editAddress" value="123 Main Street, Makati City, Metro Manila">
          </div>
          <div class="mb-3">
            <label for="editBio" class="form-label">Bio</label>
            <textarea class="form-control" id="editBio" rows="3">Hospital administrator with over 5 years of experience in healthcare management. Specializing in system administration and process optimization.</textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" style="background-color: #4A628A; border-color: #4A628A;">Save Changes</button>
      </div>
    </div>
  </div>
</div>

<!-- Change Photo Modal -->
<div class="modal fade" id="changePhotoModal" tabindex="-1" aria-labelledby="changePhotoModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="changePhotoModalLabel">Change Profile Photo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="text-center mb-4">
          <img src="logo.png" alt="Current Profile Picture" class="rounded-circle img-thumbnail mb-3" style="width: 150px; height: 150px; object-fit: cover;">
          <h6>Current Photo</h6>
        </div>
        <div class="mb-3">
          <label for="profilePhotoUpload" class="form-label">Upload New Photo</label>
          <input class="form-control" type="file" id="profilePhotoUpload" accept="image/*">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-danger me-auto">Remove Photo</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" style="background-color: #4A628A; border-color: #4A628A;">Upload</button>
      </div>
    </div>
  </div>
</div>

<script>
  // Initialize tooltips
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl)
  });

  // Preview uploaded image
  document.getElementById('profilePhotoUpload').addEventListener('change', function(e) {
    if (e.target.files && e.target.files[0]) {
      var reader = new FileReader();
      reader.onload = function(e) {
        document.querySelector('#changePhotoModal .rounded-circle').setAttribute('src', e.target.result);
      }
      reader.readAsDataURL(e.target.files[0]);
    }
  });
  
  // Check if dark mode is enabled
  document.addEventListener('DOMContentLoaded', function() {
    // Apply dark mode if it's enabled in localStorage
    if (localStorage.getItem('darkMode') === 'true') {
      document.body.classList.add('dark-mode');
    }
  });

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