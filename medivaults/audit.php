<?php
session_start();
include 'php/config.php'; 


if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['username'];
$avatarInitials = strtoupper(substr($user_id, 0, 2));
$currentPage = 'audit_log';


$stats = [
    'total_activities_today' => 0,
    'record_updates' => 0,
    'new_entries' => 0,
    'deletions' => 0,
];


$audit_logs = [];

if (isset($pdo)) {
    try {
        $today = date('Y-m-d');

        
        $stats['total_activities_today'] = $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE DATE(timestamp) = '{$today}'")->fetchColumn();

        
        $stats['record_updates'] = $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE DATE(timestamp) = '{$today}' AND action = 'Update'")->fetchColumn();

        
        $stats['new_entries'] = $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE DATE(timestamp) = '{$today}' AND action = 'Create'")->fetchColumn();

        
        $stats['deletions'] = $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE DATE(timestamp) = '{$today}' AND action = 'Delete'")->fetchColumn();

        
        $sql = "SELECT staff_name, action, module, details, ip_address, timestamp
                FROM audit_logs
                ORDER BY timestamp DESC";
        $stmt = $pdo->query($sql);
        $audit_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Audit log fetch failed: " . $e->getMessage());
    }
}


function get_action_type_class(string $action_type): string {
    return match (strtolower($action_type)) {
        'create', 'restock' => 'create',
        'update', 'decrement', 'view' => 'update',
        'delete' => 'delete',
        default => 'update',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MediVault - Audit Trail</title>
<link rel="stylesheet" href="css/audit.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
<style>

</style>
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
                    <div class="user-avatar-wrapper" role="button" tabindex="0">
                        <div class="avatar" id="userAvatar"><?= $avatarInitials ?></div>
                    </div>
                    <div class="dropdown-menu" id="userDropdownMenu">
                        <div class="account-info"><?= htmlspecialchars($_SESSION['username']) ?></div>
                        <a href="settings.php" class="menu-item"><i class="fas fa-cog"></i> Settings</a>
                        <a href="logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
        

        <div class="audit-grid-layout">
            <div class="dashboard-card" style="border-left-color: #007bff;">
                <div class="card-title">Total Activities Today</div>
                <div class="card-count"><?= htmlspecialchars($stats['total_activities_today']) ?></div>
            </div>
            <div class="dashboard-card" style="border-left-color: #007bff;">
                <div class="card-title">Record Updates</div>
                <div class="card-count"><?= htmlspecialchars($stats['record_updates']) ?></div>
            </div>
            <div class="dashboard-card" style="border-left-color: #28a745;">
                <div class="card-title">New Entries</div>
                <div class="card-count"><?= htmlspecialchars($stats['new_entries']) ?></div>
            </div>
            <div class="dashboard-card" style="border-left-color: #dc3545;">
                <div class="card-title">Deletions</div>
                <div class="card-count"><?= htmlspecialchars($stats['deletions']) ?></div>
            </div>
        </div>


        <div class="panel">
            <h3>System Activity Log</h3>
            <p class="muted">Complete record of all system actions</p>

            <div class="log-controls">
                <label>From Date <input type="date" id="from-date-filter"></label>
                <label>To Date <input type="date" id="to-date-filter"></label>
                <label>Filter by User
                    <select id="user-filter">
                        <option value="">All Users</option>
                        <option value="<?= htmlspecialchars($user_id) ?>"><?= htmlspecialchars($user_id) ?></option>
                    </select>
                </label>
            </div>

            <div class="table-wrapper">
    <table id="audit-log-table" class="audit-log-table data-table">
        <thead>
            <tr>
                <th>User</th>
                <th>Action</th>
                <th>Module</th>
                <th>Type</th>
                <th>Date</th>
                <th>Time</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($audit_logs)): ?>
                <tr><td colspan="6">No audit activities found.</td></tr>
            <?php else: foreach($audit_logs as $log): 
                $badge_class = get_action_type_class($log['action']);
            ?>
            <tr>
                <td><?= htmlspecialchars($log['staff_name']) ?></td>
                <td><?= htmlspecialchars($log['details']) ?></td>
                <td><?= htmlspecialchars($log['module']) ?></td>
                <td><span class="action-badge <?= $badge_class ?>"><?= htmlspecialchars($log['action']) ?></span></td>
                <td><?= htmlspecialchars(date('Y-m-d', strtotime($log['timestamp']))) ?></td>
                <td><?= htmlspecialchars(date('H:i:s', strtotime($log['timestamp']))) ?></td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

        </div>
    </main>
</div>
<script src="js/main.js"></script>
</body>
</html>
