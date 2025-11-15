<?php
session_start();

include 'php/config.php'; 

$accessLevel = $_SESSION['access_level'] ?? '';

if ($accessLevel === 'Limited - view only') {
    $readonly = true;
} else {
    $readonly = false;
}



if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}


$avatarInitials = strtoupper(substr($_SESSION['username'], 0, 2));


$currentPage = 'staff'; 


$roles = ['Doctor', 'Nurse', 'Admin', 'Lab Technician', 'Pharmacist'];
$departments = ['Cardiology', 'Neurology', 'Emergency', 'Pediatrics', 'Laboratory', 'Pharmacy'];
$accessLevels = [
    'Full Access - All modules', 
    'Medical Records Only', 
    'Appointments and scheduling', 
    'Inventory management', 
    'Limited - view only'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>MediVault - Staff Management</title>

    <link rel="stylesheet" href="css/staffstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
</head>
<body data-access="<?php echo htmlspecialchars($_SESSION['access_level'] ?? '', ENT_QUOTES); ?>">

    <div class="app">
        <?php include 'sidebar.php'; ?>

        <main class="main">
            <div class="topbar">
                <div class="search-bar"></div>
                
                <div class="user-actions">
                    <div class="notification-bell">
                        <i class="fas fa-bell"></i> 
                    </div>

                    <div class="user-menu-container" id="userMenuContainer">
                        <div class="user-avatar-wrapper" aria-controls="userDropdownMenu" aria-expanded="false" role="button" tabindex="0">
                            <div class="avatar" id="userAvatar"><?= $avatarInitials ?></div> 
                        </div>
                        <div class="dropdown-menu" id="userDropdownMenu">
                            <div class="account-info" id="dropdownUsernameDisplay"><?= htmlspecialchars($_SESSION['username']) ?></div>
                            
                            <a href="settings.php" class="menu-item"><i class="fas fa-cog"></i> Settings</a>
                            <a href="logout.php" class="menu-item" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </div>


            <div class="page-title-row">
                <div class="page-title">
                    <h2>Staff Management</h2>
                    <p>Manage staff roles and access levels</p>
                </div>
           
                <?php if (!$readonly): ?>
                    <button class="btn btn-primary" id="addStaffBtn">
                        <i class="fas fa-plus"></i> Add Staff Member
                    </button>
                    <?php endif; ?>
            </div>

            <div class="panel staff-directory-panel">
                <div class="panel-header">
                    <div>
                        <h3>Staff Directory</h3>
                        <h3 id="h3gray" class="muted">View and manage all staff members and their roles</h3>
                    </div>
                    
                </div>

                <div class="filter-bar">
                    <div class="search-input-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search by name, ID, or email..." class="search-input">
                    </div>

                    
                    <div class="dropdown-filter" id="roleFilter">
                        <button class="btn-filter" aria-expanded="false">
                            All Roles <i class="fas fa-caret-down"></i>
                        </button>
                        <div class="dropdown-content">
                            <a href="#">All Roles</a>
                            <?php foreach ($roles as $role): ?>
                                <a href="#"><?= $role ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                </div>

                <div class="table-responsive">
                    <table class="staff-table">
                        <thead>
                            <tr>
                                <th>Staff ID</th>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Department</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="staffTableBody">
                            <tr>
                                <td colspan="7" class="empty-state-cell">
                                    No staff members found. Click "Add Staff Member" to begin.
                                </td>
                            </tr>
                        </tbody>

                    </table>
                </div>
            </div>
        </main>
    </div>

    <div id="addStaffModal" class="modal-overlay" style="display: none;">
        <div class="modal">
            <div class="modal-header">
                <h3>Add Staff Member</h3>
                <button class="close-btn" id="closeModalBtn"><i class="fas fa-times"></i></button>
            </div>
            <p class="muted">Create a new staff account with role-based access</p>
            <form id="addStaffForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="fullName">Full Name</label>
                        <input type="text" id="fullName" name="fullName" placeholder="Enter full name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="Enter email" required>
                    </div>
                    <div class="form-group">
                        <label for="role">Role</label>
                        <select id="role" name="role" required>
                            <option value="">Select role</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= htmlspecialchars($role) ?>"><?= htmlspecialchars($role) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="department">Department</label>
                        <select id="department" name="department" required>
                            <option value="">Select department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                        <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone" placeholder="Enter phone number" required>
                        </div>
                    <div class="form-group access-level-full-width">
                        <label for="accessLevel">Access Level</label>
                        <select id="accessLevel" name="accessLevel" required>
                            <option value="">Select access level</option>
                            <?php foreach ($accessLevels as $level): ?>
                                <option value="<?= htmlspecialchars($level) ?>"><?= htmlspecialchars($level) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" placeholder="Enter username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Temporary Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter password" required>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" id="cancelModalBtn">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Account</button>
                </div>
            </form>
        </div>
    </div>

<div id="viewStaffModal" class="modal-overlay" style="display:none;">
  <div class="modal">
    <div class="modal-header">
      <h3>Staff Details</h3>
      <button class="close-btn" onclick="document.getElementById('viewStaffModal').style.display='none'">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <div class="modal-content">
      <p><strong>Name:</strong> <span id="view-name"></span></p>
      <p><strong>Role:</strong> <span id="view-role"></span></p>
      <p><strong>Department:</strong> <span id="view-dept"></span></p>
      <p><strong>Email:</strong> <span id="view-email"></span></p>
      <p><strong>Status:</strong> <span id="view-status"></span></p>
    </div>
  </div>
</div>

<div id="editStaffModal" class="modal-overlay" style="display:none;">
  <div class="modal">
    <div class="modal-header">
      <h3>Edit Staff</h3>
      <button class="close-btn" onclick="document.getElementById('editStaffModal').style.display='none'">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <form id="editStaffForm">
      <input type="hidden" id="edit-id" name="staff_id">

      <div class="form-group">
        <label>Name</label>
        <input type="text" id="edit-name" name="name" required>
      </div>

      <div class="form-group">
        <label>Role</label>
        <input type="text" id="edit-role" name="role" required>
      </div>

      <div class="form-group">
        <label>Department</label>
        <input type="text" id="edit-department" name="department" required>
      </div>

      <div class="form-group">
        <label>Email</label>
        <input type="email" id="edit-email" name="email" required>
      </div>

      <div class="form-group">
        <label>Status</label>
        <select id="edit-status" name="status" required>
          <option value="Active">Active</option>
          <option value="Inactive">Inactive</option>
        </select>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('editStaffModal').style.display='none'">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

    
     <script src="js/main.js"></script>
    <script src="js/staff.js"></script>
</body>
</html>
