<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">
  <div class="brand">
    <div class="logo"><img src="assets/images/logonga.PNG" alt="MediVault logo"></div>
    <div>
      <h1>MediVault</h1>
      <div>Hospital System</div>
    </div>
  </div>

  <nav class="nav" aria-label="Main navigation">
    <a class="<?= $currentPage=='dashboard.php'?'active':'' ?>" href="dashboard.php"><span class="icon"><i class="fas fa-home"></i></span> Dashboard</a>
    <a class="<?= $currentPage=='patients.php'?'active':'' ?>" href="patients.php"><span class="icon"><i class="fas fa-users"></i></span> Patients</a>
    <a class="<?= $currentPage=='patient-identification.php'?'active':'' ?>" href="patient-identification.php"><span class="icon"><i class="fas fa-id-card"></i></span> Patient ID</a>
    <a class="<?= $currentPage=='appointments.php'?'active':'' ?>" href="appointments.php"><span class="icon"><i class="fas fa-calendar-alt"></i></span> Appointments</a>
    <a class="<?= $currentPage=='inventory.php'?'active':'' ?>" href="inventory.php"><span class="icon"><i class="fas fa-boxes"></i></span> Inventory</a>
    <a class="<?= $currentPage=='notif.php'?'active':'' ?>" href="notif.php"><span class="icon"><i class="fas fa-bell"></i></span> Notifications</a>
    <a class="<?= $currentPage=='staff.php'?'active':'' ?>" href="staff.php"><span class="icon"><i class="fas fa-users-cog"></i></span> Staff Management</a>
    <a class="<?= $currentPage=='audit.php'?'active':'' ?>" href="audit.php"><span class="icon"><i class="fas fa-file-lines"></i></span> Audit Log</a>
    <a class="<?= $currentPage=='backup.php'?'active':'' ?>" href="backup.php"><span class="icon"><i class="fas fa-database"></i></span> Backup</a>
    <a class="<?= $currentPage=='settings.php'?'active':'' ?>" href="settings.php"><span class="icon"><i class="fas fa-cog"></i></span> Settings</a>
  </nav>

  <div>v0.1 • Medivault (Beta)</div>
</aside>
