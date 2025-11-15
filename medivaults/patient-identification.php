<?php
session_start();
include 'php/config.php';


if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>MediVault - Patient Identification</title>
  <link rel="stylesheet" href="css/idstyle.css">
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
          <h2>Patient Identification</h2>
          <p>Identify patients using biometric authentication</p>
        </div>
      </div>

      <div class="grid two-equal-columns">
        <div class="panel">
    <h3>Biometric Scanner</h3>
    <h3 id="h3gray">Scan fingerprint or face to identify patient</h3>
    
    <div class="scanner-buttons-row">
        <button class="scan-button">
            <span class="icon"><i class="fas fa-fingerprint"></i></span>
            Scan Fingerprint
        </button>
        <button id="scanFaceBtn" class="scan-button btn-scan">
            <span class="icon"><i class="fas fa-camera"></i></span>
            Scan Face
        </button>
    </div>
    
    <div class="scanner-status-area">
        <div id="face-success-message" style="display:none;" class="success-message">
            <i class="fas fa-check-circle"></i> Patient identified successfully
        </div>

        <button type="button" id="scan-another-btn" style="display:none;" class="scan-button btn-secondary">
            Scan Another Patient
        </button>
    </div>

    <div id="faceModal" class="modal">
        <div class="modal-content">
            <video id="videoFeed" width="320" height="240" autoplay muted></video>
            <div class="modal-buttons">
                <button id="closeFaceModal">Cancel</button>
                <button id="captureFace">Capture</button>
            </div>
        </div>
    </div>
</div>
       

        <div class="panel">
          <h3>Patient Details</h3>
          <h3 id="h3gray">Information retrieved from biometric scan</h3>
          <div class="patient-placeholder">
            <div class="empty-message">
              <p>No patient data available</p>
              <p>Scan a patient to view their details</p>
            </div>
          </div>

          
        </div>
      </div>
    </main>
  </div>

  <script src="js/face-api.min.js"></script>
  <script src="js/scan-face.js"></script>
  <script src="js/main.js"></script>
</body>
</html>
