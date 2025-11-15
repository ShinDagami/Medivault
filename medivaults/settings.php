<?php
session_start();



if (!isset($_SESSION['username'])) {
    
    header("Location: index.php");
    exit;
}

$currentPage = 'settings'; 
$avatarInitials = strtoupper(substr($_SESSION['username'] ?? 'User', 0, 2));
$currentUsername = htmlspecialchars($_SESSION['username'] ?? 'Guest');


function fetchSystemSettings() {
    
    if (isset($_SESSION['system_settings'])) {
        return $_SESSION['system_settings'];
    }
    
    
    return [
        'hospital_name' => 'MediVault Hospital',
        'email_address' => 'info@medivault.com',
        'phone_number' => '+63 47 237 1234',
        'address' => 'Medical Center Drive, Balanga, Bataan'
    ];
}


$currentSettings = fetchSystemSettings();


$hospitalName = htmlspecialchars($currentSettings['hospital_name']);
$currentEmail = htmlspecialchars($currentSettings['email_address']);
$currentPhone = htmlspecialchars($currentSettings['phone_number']);
$currentAddress = htmlspecialchars($currentSettings['address']);


$isAutoLogoutEnabled = true; 
$timeoutDuration = '30 minutes';


$message = '';
$statusType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_general') {
        
        $newHospitalName = trim($_POST['hospital_name']);
        $newEmail = filter_var(trim($_POST['email_address']), FILTER_SANITIZE_EMAIL);
        $newPhone = trim($_POST['phone_number']);
        $newAddress = trim($_POST['address']);

        
        if (empty($newHospitalName) || empty($newEmail)) {
            $message = "Hospital Name and Email are required fields.";
            $statusType = 'error';
        } else {
            
            $_SESSION['system_settings'] = [
                'hospital_name' => $newHospitalName,
                'email_address' => $newEmail,
                'phone_number' => $newPhone,
                'address' => $newAddress,
            ];

            $message = "General settings updated successfully (Saved to session).";
            $statusType = 'success';
        }
    }
    
    
    $_SESSION['status_message'] = $message;
    $_SESSION['status_type'] = $statusType;
    header("Location: settings.php");
    exit;
}


if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    $statusType = $_SESSION['status_type'];
    unset($_SESSION['status_message']);
    unset($_SESSION['status_type']);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MediVault - System Settings</title>
<link rel="stylesheet" href="css/dbstyle.css"> 
<link rel="stylesheet" href="css/settings.css"> 
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
        <div class="account-info" id="dropdownUsernameDisplay"><?= $currentUsername ?></div>
        <a href="settings.php" class="menu-item"><i class="fas fa-cog"></i> Settings</a>
        <a href="logout.php" class="menu-item" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Logout</a>
       </div>
      </div>
     </div>
    </div>

        <div class="page-title">
            <h2>Settings</h2>
            <p>Configure system preferences and options</p>
        </div>

        <?php if ($message): ?>
         
            <div id="status-message" class="status-box <?= $statusType ?> mb-20" role="status">
                <?= $message ?>
            </div>
        <?php endif; ?>

    
        <div class="settings-container">
            
         
            <div class="settings-column left-column">
                
   
                <div class="settings-panel">
                    <h3>General Settings</h3>
                    <p class="muted-panel">Basic system configuration</p>
                    <form method="POST" action="settings.php" id="general-settings-form" class="settings-form">
                        <input type="hidden" name="action" value="update_general">
                        
                        <div class="form-group-half">
                            <div class="form-group">
                                <label for="hospital_name">Hospital Name</label>
                        
                                <input type="text" id="hospital_name" name="hospital_name" class="input-field" value="<?= $hospitalName ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email_address">Email Address</label>
                         
                                <input type="email" id="email_address" name="email_address" class="input-field" value="<?= $currentEmail ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group-half">
                            <div class="form-group">
                                <label for="phone_number">Phone Number</label>
                          
                                <input type="text" id="phone_number" name="phone_number" class="input-field" value="<?= $currentPhone ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address">Address</label>
                     
                            <input type="text" id="address" name="address" class="input-field" value="<?= $currentAddress ?>">
                        </div>
                    </form>
                </div>
                
             
                <div class="settings-panel">
                    <h3>System Preferences</h3>
                    <div class="setting-item">
                        <div class="setting-label">
                            <label for="dark_mode">Dark Mode</label>
                            <p class="setting-desc">Enable dark theme</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="dark_mode" name="dark_mode">
                            <span class="slider round"></span>
                        </label>
                    </div>

                    <div class="setting-item">
                        <div class="setting-label">
                            <label for="email_notifications">Email Notifications</label>
                            <p class="setting-desc">Receive system alerts via email</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="email_notifications" name="email_notifications" checked>
                            <span class="slider round"></span>
                        </label>
                    </div>

                    <div class="setting-item">
                        <div class="setting-label">
                            <label for="sms_notifications">SMS Notifications</label>
                            <p class="setting-desc">Receive system alerts via SMS</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="sms_notifications" name="sms_notifications" checked>
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>

                <div class="settings-panel">
                    <h3>Regional Settings</h3>
                    <div class="form-group-half">
                        <div class="form-group">
                            <label for="timezone">Timezone</label>
                            <select id="timezone" name="timezone" class="input-field">
                                <option>Eastern Time (EST)</option>
                                <option>GMT +8 (Manila)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="date_format">Date Format</label>
                            <input type="text" id="date_format" name="date_format" class="input-field" value="MM/DD/YYYY">
                        </div>
                    </div>
                </div>

                <div class="settings-actions">
                    <button type="button" class="btn btn-secondary">Cancel</button>
            
                    <button type="submit" form="general-settings-form" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                </div>

            </div>
            

            <div class="settings-column right-column">
    
                <div class="settings-panel security-panel">
                    <h3>Security</h3>
                    <p class="muted-panel">Manage security options</p>
                    
                    <h4>Change Password</h4>
                    <button class="btn btn-secondary">Update Password</button>

                    <h4 class="mt-20">Session Settings</h4>
                    <div class="setting-item">
                        <div class="setting-label">
                            <label for="auto_logout">Auto Logout</label>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="auto_logout" name="auto_logout" <?= $isAutoLogoutEnabled ? 'checked' : '' ?>>
                            <span class="slider round"></span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label for="timeout_duration">Timeout Duration</label>
                        <select id="timeout_duration" name="timeout_duration" class="input-field">
                            <option value="30 minutes" selected>30 minutes</option>
                            <option value="60 minutes">60 minutes</option>
                        </select>
                    </div>

                    <h4 class="mt-20">Two-Factor Authentication</h4>
                    <div class="setting-item">
                        <div class="setting-label">
                            <label for="enable_2fa">Enable 2FA</label>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="enable_2fa" name="enable_2fa">
                            <span class="slider round"></span>
                        </label>
                    </div>
                    <button class="btn btn-secondary mt-10" disabled>Configure 2FA</button>

                    <h4 class="mt-20">Activity Log</h4>
                    <p class="setting-desc">Last login: Today at 8:30 AM</p>
                    <button class="btn btn-secondary mt-10">View Login History</button>
                </div>

                <div class="settings-panel system-info-panel">
                    <h3>System Information</h3>
                    <p class="muted-panel">Current system status and details</p>
                    
                    <div class="info-row">
                        <span>System Version</span>
                        <strong>V2.4.1</strong>
                    </div>
                    <div class="info-row">
                        <span>Database Status</span>
                        <strong class="status-disconnected">Disconnected (Using Session)</strong>
                    </div>
                    <div class="info-row">
                        <span>Storage Used</span>
                        <strong>45.2 GB / 100 GB</strong>
                    </div>
                    <div class="info-row">
                        <span>Active Users</span>
                        <strong>87 staff members</strong>
                    </div>
                </div>

            </div>

        </div> 
        
    </main>
</div>

<script src="js/main.js"></script>
</body>
</html>