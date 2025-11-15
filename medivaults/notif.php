<?php
session_start();
include 'php/config.php'; 


if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

$avatarInitials = strtoupper(substr($_SESSION['username'], 0, 2));
$currentPage = 'notifications'; 




$stats_today = $stats_week = $stats_failed = 0;

if (isset($pdo)) {
    try {
        
        $stats_today = $pdo->query("SELECT COUNT(*) FROM notifications WHERE DATE(sent_at) = CURDATE()")->fetchColumn();

        
        $stats_week = $pdo->query("SELECT COUNT(*) FROM notifications WHERE YEARWEEK(sent_at, 1) = YEARWEEK(CURDATE(), 1)")->fetchColumn();

        
        $stats_failed = $pdo->query("SELECT COUNT(*) FROM notifications WHERE status = 'failed'")->fetchColumn();
    } catch (PDOException $e) {
        error_log("Failed to fetch notification stats: " . $e->getMessage());
    }
}



?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-m">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MediVault - Notifications</title>

<link rel="stylesheet" href="css/notif.css">
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

        <div class="page-title">
            <div class="title-group">
                <h2>Notifications</h2>
                <p>Manage SMS and email alerts to patients and staff</p>
            </div>
            <div class="actions">
                <button class="btn btn-primary" id="compose-notification-btn"><i class="fas fa-plus"></i> Compose Notification</button>
            </div>
        </div>

   
        <div class="notifications-grid">
         
            <div class="notifications-sidebar">
                <div class="panel settings-panel">
                    <h3>Notification Settings</h3>
                    <p class="muted">Configure automatic alerts</p>
                    
                    <div class="setting-item">
                        <div class="setting-label">
                            <i class="fas fa-power-off"></i>
                            <span>Automatic Alerts</span>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="toggle-auto-alerts" checked>
                            <span class="slider round"></span>
                        </label>
                    </div>
                    <div class="setting-item">
                        <div class="setting-label">
                            <i class="fas fa-calendar-check"></i>
                            <span>Appointment Reminders</span>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="toggle-appt-reminders" checked>
                            <span class="slider round"></span>
                        </label>
                    </div>
                    <div class="setting-item">
                        <div class="setting-label">
                            <i class="fas fa-flask"></i>
                            <span>Lab Result Alerts</span>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="toggle-lab-alerts" checked>
                            <span class="slider round"></span>
                        </label>
                    </div>
                    <div class="setting-item">
                        <div class="setting-label">
                            <i class="fas fa-prescription-bottle-alt"></i>
                            <span>Medication Reminders</span>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="toggle-med-reminders" checked>
                            <span class="slider round"></span>
                        </label>
                    </div>
                    <div class="setting-item">
                        <div class="setting-label">
                            <i class="fas fa-procedures"></i>
                            <span>Emergency Alerts</span>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="toggle-emergency-alerts" checked>
                            <span class="slider round"></span>
                        </label>
                    </div>
                    <div class="setting-item">
                        <div class="setting-label">
                            <i class="fas fa-users-cog"></i>
                            <span>Staff Schedule Updates</span>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="toggle-staff-updates" checked>
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>

                <div class="panel stats-panel">
                    <h3>Statistics</h3>
                    <div class="stat-item">
                        <span>Sent Today</span>
                        <span class="stat-value" id="stat-today"><?= $stats_today ?></span>
                    </div>
                    <div class="stat-item">
                        <span>Sent This Week</span>
                        <span class="stat-value" id="stat-week"><?= $stats_week ?></span>
                    </div>
                    <div class="stat-item">
                        <span>Failed</span>
                        <span class="stat-value failed" id="stat-failed"><?= $stats_failed ?></span>
                    </div>
                </div>
            </div>

        
            <div class="notifications-main">
                <div class="panel history-panel">
                    <h3>Recent Notifications</h3>
                    <p class="muted">History of sent SMS and email alerts</p>
                    <div class="notifications-list" id="notifications-list-container">
                        <div class="loading-shimmer"></div>
                        <div class="loading-shimmer"></div>
                        <div class="loading-shimmer"></div>
                    </div>
                </div>
            </div>

        </div>

    </main> 
</div>


<div id="compose-notification-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Send Notification</h3>
            <span class="close-btn">&times;</span>
        </div>
        <form id="compose-notification-form">
            <div class="modal-body">
                <div class="form-group">
                    <label for="notification-type">Notification Type</label>
                    <select id="notification-type" name="type" required>
                        <option value="">Select type</option>
                        <option value="sms">SMS</option>
                        <option value="email">Email</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="recipient-type">Recipient Type</label>
                    <select id="recipient-type" name="recipient_type" required>
                    <option value="">Select recipient</option>
                    <option value="patient">Patient</option>
                    <option value="patient_family">Patient Family</option>
                    <option value="staff">Staff Member</option>
                    <option value="group">Group (e.g., All Staff)</option>
                    <option value="custom">Custom</option>
                </select>
                </div>
                <div class="form-group">
                    <label for="recipient">Recipient</label>
                    <input type="text" id="recipient" name="recipient" placeholder="Enter name, phone, or email" required>
         
                </div>
                <div class="form-group">
                    <label for="subject">Subject (for Email)</label>
                    <input type="text" id="subject" name="subject" placeholder="Enter subject">
                </div>
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" rows="5" placeholder="Enter your message..." required></textarea>
                </div>
                <div id="compose-form-error" class="form-error-message" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary cancel-modal-btn">Cancel</button>
                <button type="submit" class="btn btn-primary">Send Notification</button>
            </div>
        </form>
    </div>
</div>

<script src="js/main.js"></script> 
<script src="js/notif.js"></script> 
</body>
</html>