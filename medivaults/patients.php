<?php
session_start();
include 'php/config.php'; 


if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}


$patients = [];


if (isset($pdo)) {
    try {
        
        
        $sql = "SELECT * FROM patients ORDER BY created_at DESC";
        $stmt = $pdo->query($sql);
        
        
        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        
        error_log("Patient list fetch failed: " . $e->getMessage());
        
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="utf-8" />
 <meta name="viewport" content="width=device-width, initial-scale=1" />
 <title>MediVault - Patients</title>
 <link rel="stylesheet" href="css/pstyle.css">
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
</head>
<body>
 <div class="app">
  <?php include 'sidebar.php'; ?>

  <main class="main">
   <div class="topbar">
    <div class="search-bar"></div>
    <div class="user-actions">
     <div class="notification-bell"><i class="fas fa-bell"></i></div>
     <div class="user-menu-container" id="userMenuContainer">
      <div class="user-avatar-wrapper" aria-controls="userDropdownMenu" aria-expanded="false" role="button" tabindex="0">
       <div class="avatar" id="userAvatar"><?= strtoupper($_SESSION['username'][0] . $_SESSION['username'][1]) ?></div>
      </div>
      <div class="dropdown-menu" id="userDropdownMenu">
       <div class="account-info" id="dropdownUsernameDisplay"><?= htmlspecialchars($_SESSION['username']) ?></div>
       <a href="settings.php" class="menu-item"><i class="fas fa-cog"></i> Settings</a>
       <a href="logout.php" class="menu-item" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </div>
     </div>
    </div>
   </div>

   <div class="page-header">
    <div class="page-title">
     <h2>Patients</h2>
     <p>Register and manage patient records</p>
    </div>
    <button class="btn-primary" id="open-register-modal">
     <i class="fas fa-plus"></i> Register New Patient
    </button>
   </div>

   <div class="grid">
    <div class="panel">
     <h3>Patient Records</h3>
     <h3 id="h3gray">View and manage all registered patients</h3>

     <div class="patient-controls">
      <div class="search-input-wrapper">
       <i class="fas fa-search"></i>
       <input id="search-input" placeholder="Search by name, ID, or contact..." aria-label="Search" />
      </div>

      <div class="gender-dropdown">
       <div class="dropdown-toggle" id="gender-filter-toggle">
        <span id="gender-filter-text">All Genders</span>
        <i class="fas fa-chevron-down"></i>
       </div>
       <div class="dropdown-menu1" id="gender-dropdown-menu">
        <div class="dropdown-item active">All Genders</div>
        <div class="dropdown-item">Male</div>
        <div class="dropdown-item">Female</div>
       </div>
      </div>

     </div>

     <table id="patients-tables" class="patient-table" border="1">
      <thead>
        <tr>
        <th>Patient ID</th>
        <th>Name</th>
        <th>Age</th>
        <th>Gender</th>
        <th>Contact</th>
        <th>Status</th>
        <th>Actions</th>
        </tr>
      </thead>
      <tbody>
       <?php 
              if (empty($patients)): 
              ?>
                <tr><td colspan="7" class="empty-state-cell">No patient records found.</td></tr>
              <?php
              else:
              foreach($patients as $p): ?>
        <tr>
         <td><?= htmlspecialchars($p['patient_id']) ?></td>
         <td><?= htmlspecialchars($p['name']) ?></td>
         <td><?= htmlspecialchars($p['age']) ?></td>
         <td><?= htmlspecialchars($p['gender']) ?></td>
         <td><?= htmlspecialchars($p['contact']) ?></td>
         <td><?= htmlspecialchars($p['status']) ?></td>
         <td>
          <button class="btn btn-sm btn-action btn-view" data-id="<?= $p['id'] ?>"><i class="fas fa-eye"></i></button>
          <button class="btn btn-sm btn-action btn-edit" data-id="<?= $p['id'] ?>" class="fas fa-edit"><i class="fas fa-edit"></i></button>
          <button class="btn btn-sm btn-action btn-delete" data-id="<?= $p['id'] ?>"><i class="fas fa-trash"></i></button>
         </td>
        </tr>
       <?php endforeach;
              endif; ?>
      </tbody>
      </table>
    </div>
   </div>
  </main>
 </div>

  <?php include 'modals.php'; ?>

 <script src="js/face-api.min.js"></script>
 <script src="js/main.js"></script>
 <script src="js/patient.js"></script>
</body>
</html>