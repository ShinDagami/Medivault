<?php
session_start();
include 'php/config.php'; 

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

$avatarInitials = strtoupper(substr($_SESSION['username'], 0, 2));
$currentPage = 'backup'; 


$lastBackup = ['date' => 'N/A', 'time' => 'N/A'];
$nextScheduled = ['date' => 'N/A', 'time' => 'N/A', 'status' => ''];
$totalBackupSize = '0 GB';
$totalBackupFiles = 0;
$backupHistory = [];


$dailyBackupStatus = 'Enabled';
$dailyBackupTime = '03:00 AM';
$dailyBackupRetention = '7 days';
$weeklyBackupStatus = 'Enabled';
$weeklyBackupDay = 'Sunday';
$weeklyBackupRetention = '4 weeks';

if (isset($pdo)) {
    try {
        
        $stmtLast = $pdo->query("SELECT created_at, size_mb FROM backups WHERE status='success' ORDER BY created_at DESC LIMIT 1");
        $last = $stmtLast->fetch(PDO::FETCH_ASSOC);
        if ($last) {
            $lastBackup['date'] = date('F d, Y', strtotime($last['created_at']));
            $lastBackup['time'] = date('h:i A', strtotime($last['created_at']));
        }
        
        
        
        $stmtHistory = $pdo->query("SELECT type, created_at, size_mb, status, file_path FROM backups WHERE status='success' ORDER BY created_at DESC LIMIT 5");
        $backupHistory = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

        
        $stmtTotal = $pdo->query("SELECT SUM(size_mb) AS total_size, COUNT(id) AS total_files FROM backups WHERE status='success'");
        $summary = $stmtTotal->fetch(PDO::FETCH_ASSOC);

        if ($summary && $summary['total_size'] !== null) {
            $totalGB = round($summary['total_size'] / 1024, 1); 
            $totalBackupSize = $totalGB . ' GB';
            $totalBackupFiles = $summary['total_files'];
        }
        
        
        $today = date('Y-m-d');
        if ($last && date('Y-m-d', strtotime($last['created_at'])) == $today) {
            $nextScheduled['date'] = date('F d, Y', strtotime('+1 day'));
        } else {
            $nextScheduled['date'] = date('F d, Y', strtotime('today'));
        }
        $nextScheduled['time'] = $dailyBackupTime;
        $nextScheduled['status'] = '(Automatic)';


    } catch (PDOException $e) {
        error_log("Backup data fetch failed: " . $e->getMessage());
        
    }
}



function formatBackupRow(array $backup): string {
    $date = date('F d', strtotime($backup['created_at']));
    $time = date('h:i A', strtotime($backup['created_at']));
    $fullDate = date('Y-m-d', strtotime($backup['created_at']));
    
    
    $size = number_format($backup['size_mb'] / 1024, 1) . ' GB'; 
    $statusClass = $backup['status'] === 'success' ? 'success' : 'failed';
    $statusText = ucfirst($backup['status']);

    
    $typeText = ucfirst($backup['type']);
    $titleText = "{$typeText} Backup - $date";
    
    
    $filename = basename($backup['file_path'] ?? ''); 

    $html = <<<HTML
    <div class="history-item" data-backup-id="{$fullDate}-{$time}" data-filepath="{$filename}">
        <div class="status-icon"><i class="fas fa-check-circle"></i></div>
        <div class="history-info">
            <div class="backup-type-title">$titleText</div>
            <div class="muted">$fullDate at $time</div>
        </div>
        <div class="backup-size">$size</div>
        <div class="backup-status-tag status-tag {$statusClass}">{$statusText}</div>
        <a href="#" class="download-link" data-backup-action="download" title="Download"><i class="fas fa-cloud-download-alt"></i></a>
    </div>
    HTML;
    return $html;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MediVault - Backup & Recovery</title>
<link rel="stylesheet" href="css/dbstyle.css"> 
<link rel="stylesheet" href="css/backup.css">
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

        <div class="page-title">
            <h2>Backup & Recovery</h2>
            <p>Manage database backups and data recovery</p>
        </div>

        <div id="status-message" class="status-box hidden" role="status"></div>

        <div class="backup-summary-cards">
            <div class="summary-card card">
                <h3>Last Backup</h3>
                <div class="count"><?= $lastBackup['date'] ?></div>
                <p class="muted"><?= $lastBackup['time'] ?></p>
            </div>
            <div class="summary-card card">
                <h3>Next Scheduled</h3>
                <div class="count"><?= $nextScheduled['date'] ?></div>
                <p class="muted"><?= $nextScheduled['time'] ?> <?= $nextScheduled['status'] ?></p>
            </div>
            <div class="summary-card card">
                <h3>Total Backup Size</h3>
                <div class="count"><?= $totalBackupSize ?></div>
                <p class="muted"><?= $totalBackupFiles ?> backup files</p>
            </div>
        </div>

        <div class="grid backup-grid-layout">
            <div class="backup-actions-panel">
                <div class="panel">
                    <h3>Backup Actions</h3>
                    <p class="muted">Create or restore backups</p>
                    <div class="action-button-group">
                        <button class="btn btn-primary" id="create-backup-btn"><i class="fas fa-plus-circle"></i> Create Backup Now</button>
                        <button class="btn btn-secondary" id="restore-backup-btn" disabled><i class="fas fa-undo"></i> Restore from Backup</button>
                        <button class="btn btn-secondary" id="download-backup-btn"><i class="fas fa-cloud-download-alt"></i> Download Latest Backup</button>
                    </div>
                    <div class="automatic-backup-info">
                        Automatic Backups
                        <p class="muted-light">Daily backups are scheduled at 3:00 AM. Weekly full backups every Sunday.</p>
                    </div>
                </div>
            </div>

            <div class="backup-history-panel">
                <div class="panel">
                    <h3>Backup History</h3>
                    <p class="muted">Previous backup records (Last 5)</p>
                    <div class="history-list" id="backup-history-list">
                        <?php if (empty($backupHistory)): ?>
                            <div class="empty-state-cell">No successful backup records found. Click 'Create Backup Now' to begin.</div>
                        <?php else: 
                            foreach($backupHistory as $backup):
                                echo formatBackupRow($backup);
                            endforeach;
                        endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="full-width-settings-container">
            <h3>Backup Settings</h3>
            <p class="muted">Configure automatic backup schedule</p>
            <div class="settings-table-wrapper">
                <div class="settings-group">
                    <h4>Daily Backups</h4>
                    <div class="settings-row"><span class="label">Status</span><span class="value status-enabled"><?= $dailyBackupStatus ?></span></div>
                    <div class="settings-row"><span class="label">Time</span><span class="value"><?= $dailyBackupTime ?></span></div>
                    <div class="settings-row"><span class="label">Retention</span><span class="value"><?= $dailyBackupRetention ?></span></div>
                </div>
                <div class="settings-group">
                    <h4>Weekly Backups</h4>
                    <div class="settings-row"><span class="label">Status</span><span class="value status-enabled"><?= $weeklyBackupStatus ?></span></div>
                    <div class="settings-row"><span class="label">Day</span><span class="value"><?= $weeklyBackupDay ?></span></div>
                    <div class="settings-row"><span class="label">Retention</span><span class="value"><?= $weeklyBackupRetention ?></span></div>
                </div>
            </div>
        </div>
        
    </main>
</div>

<div id="restoreModal" class="modal hidden">
    <div class="modal-content">
        <h4>Restore Database</h4>
        <p>Are you sure you want to restore the database? This action will overwrite all current data with the selected backup file. This cannot be undone.</p>
        
        <label for="backupFileSelect">Select Backup File:</label>
        <select id="backupFileSelect" class="input-field" required>
            </select>
        
        <div class="modal-actions">
            <button id="cancelRestoreBtn" class="btn btn-secondary">Cancel</button>
            <button id="confirmRestoreBtn" class="btn btn-danger">Confirm Restore</button>
        </div>
    </div>
</div>


<script src="js/main.js"></script>
<script src="js/backup.js"></script>
<script>
    
    const __app_id = typeof __app_id !== 'undefined' ? __app_id : 'default-app-id';
</script>
</body>
</html>