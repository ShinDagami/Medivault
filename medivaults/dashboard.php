<?php
session_start();

include 'php/config.php'; 


header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");


if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}


$fullName = $_SESSION['name'] ?? '';
$role = $_SESSION['role'] ?? '';
$username = $_SESSION['username'] ?? '';

$displayName = '';
if ($fullName) {
    $parts = explode(' ', $fullName);
    $last = array_pop($parts);
    $initials = '';
    foreach ($parts as $p) {
        $initials .= strtoupper($p[0]) . '. ';
    }
    $displayName = trim($initials . $last);
}

$prefix = '';
if ($role === 'Doctor') $prefix = 'Dr.';
if ($role === 'Pharmacist') $prefix = 'RPh';
if ($role === 'Nurse') $prefix = 'Nurse';
if ($role === 'Lab Technician') $prefix = 'Tech';
if ($role === 'Admin') $prefix = 'Admin';

$avatarText = strtoupper(substr($username, 0, 2));
$avatarInitials = strtoupper(substr($_SESSION['username'], 0, 2));


if (!isset($pdo)) {
    
    $totalPatients = $upcomingAppointments = $inventoryAlerts = $activeStaff = 0;
    $activitiesRes = []; 
    $overview = [];
} else {
    try {
        
        
        function fetchCount($pdo, $sql) {
            $stmt = $pdo->query($sql);
            
            return $stmt->fetchColumn() ?? 0;
        }

        
        $totalPatients = fetchCount($pdo, "SELECT COUNT(*) AS total FROM patients");

        
        $upcomingAppointments = fetchCount($pdo, "SELECT COUNT(*) AS total FROM appointments WHERE appointment_datetime >= CURDATE()");

        
        $inventoryAlerts = fetchCount($pdo, "SELECT COUNT(*) AS total FROM inventory WHERE quantity <= reorder_level");

        
        $activeStaff = fetchCount($pdo, "SELECT COUNT(*) AS total FROM staff WHERE status='Active'");

        
        $activitiesQuery = "
            SELECT s.name AS user, al.action, al.details AS target, al.module AS type, al.timestamp 
            FROM audit_logs al
            LEFT JOIN staff s ON al.user_id = s.staff_id -- Assuming user_id maps to staff_id
            ORDER BY al.timestamp DESC
            LIMIT 5
        ";
        $stmtActivities = $pdo->query($activitiesQuery);
        
        $activitiesRes = $stmtActivities->fetchAll(PDO::FETCH_ASSOC);

        
        $overview = [];
        $overview['checked_in'] = fetchCount($pdo, "SELECT COUNT(*) AS total FROM patients WHERE status='Active'");
        $overview['pending_appointments'] = fetchCount($pdo, "SELECT COUNT(*) AS total FROM appointments WHERE status='Pending' AND DATE(appointment_datetime)=CURDATE()");
        $overview['completed_consultations'] = fetchCount($pdo, "SELECT COUNT(*) AS total FROM appointments WHERE status='Completed' AND DATE(appointment_datetime)=CURDATE()");
        $overview['emergency_cases'] = fetchCount($pdo, "SELECT COUNT(*) AS total FROM patients WHERE status='Critical'");
        $overview['lab_tests_pending'] = 0;
        $overview['beds_available'] = 0;
        
    } catch (PDOException $e) {
        error_log("Dashboard data fetch failed: " . $e->getMessage());
        
        $totalPatients = $upcomingAppointments = $inventoryAlerts = $activeStaff = 0;
        $activitiesRes = [];
        $overview = ['error' => 'Data unavailable'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>MediVault - Dashboard</title>
<link rel="stylesheet" href="css/dbstyle.css">
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

    <div class="page-title">
      <h2>Dashboard</h2>
      <p>Welcome to MediVault Hospital Information System</p>
    </div>

    <section class="cards">
      <div class="card"><div class="title">Total Patients</div><div class="value"><?= $totalPatients ?></div></div>
      <div class="card"><div class="title">Upcoming Appointments</div><div class="value"><?= $upcomingAppointments ?></div></div>
      <div class="card"><div class="title">Inventory Alerts</div><div class="value"><?= $inventoryAlerts ?></div></div>
      <div class="card"><div class="title">Active Staff</div><div class="value"><?= $activeStaff ?></div></div>
    </section>

    <div class="grid">
      <div class="panel">
        <h3>Recent Activities</h3>
        <div class="activities">
          <?php 
          
          if (empty($activitiesRes)): ?>
            <div class="empty-state-cell">No recent activities found or data is unavailable.</div>
          <?php else:
            foreach($activitiesRes as $row): ?>
            <div class="activity">
              <div class="left">
                <div class="dot"></div>
                <div>
                  <div><?= htmlspecialchars($row['user']) ?>: <?= htmlspecialchars($row['action']) ?> (<?= htmlspecialchars($row['target']) ?>)</div>
                  <div class="muted"><?= date('Y-m-d H:i:s', strtotime($row['timestamp'])) ?></div>
                </div>
              </div>
            </div>
          <?php 
            endforeach;
          endif;
          ?>
        </div>
      </div>

      <aside class="panel overview">
        <h3>Today's Overview</h3>
        <?php foreach($overview as $key => $val): ?>
          <div class="row">
            <div><?= ucwords(str_replace('_',' ',$key)) ?></div>
            <div class="badge"><?= $val ?></div>
          </div>
        <?php endforeach; ?>
      </aside>
    </div>
  </main>
</div>

<script src="js/main.js"></script>
<script src="js/db.js"></script>
<script>
window.onload = function() {
    if (!<?= isset($_SESSION['username']) ? 'true' : 'false' ?>) {
        window.location.href = 'index.php';
    }
};
</script>
</body>
</html>