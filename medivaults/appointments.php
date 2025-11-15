<?php
session_start();

include 'php/config.php'; 


if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}


$avatarInitials = strtoupper(substr($_SESSION['username'], 0, 2));

$currentPage = 'appointments'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>MediVault - Appointments</title>
    <link rel="stylesheet" href="css/appstyle.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
</head>
<body>
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

           

            <div class="page-title">
                <div class="title-group"> 
                    <h2>Appointments & Queue</h2>
                    <p>Manage patient appointments and view queue status</p>
                </div>
                <button class="btn btn-primary" id="scheduleAppointmentBtn"><i class="fas fa-plus"></i> Schedule Appointment</button>
            </div>


            <div class="grid dashboard-grid">
                <div class="panel calendar-panel">
                    <h3>Calendar</h3>
                    <p class="muted">Select a date to view appointments</p>

                    <div class="calendar-widget" id="calendar-container">
                        </div>
                </div>

                <div class="panel appointments-panel">
                    <h3 id="appointments-title">Scheduled Appointments</h3>
                    <p class="muted" id="appointments-date">Scheduled appointments for <?php echo date('m/d/Y'); ?></p>

                    <div class="appointment-list" id="appointment-list">
                        <div class="empty-message">Select a date or click 'Schedule Appointment' to begin.</div>
                    </div>
                </div>
            </div>
            
            <div class="queue-section">
                <h3 class="queue-title">Current Queue</h3>
                <p class="muted queue-subtitle">Patients currently in queue</p>
                
                <div class="cards queue-cards-3" id="current-queue-container">
                    <div class="card queue-box empty-queue-box">
                        <div class="queue-number">...</div>
                        <span class="status">No Active Queue</span>
                    </div>
                </div>

            </div>

        </main>
    </div>

    <div id="scheduleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Schedule New Appointment</h3>
                <span class="close-btn">&times;</span>
            </div>
            <form id="scheduleAppointmentForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="patient_id">Patient</label>
                        <select id="patient_id" name="patient_id" required>
                            <option value="">Select patient</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="doctor_id">Doctor</label>
                        <select id="doctor_id" name="doctor_id" required>
                            <option value="">Select doctor</option>
                        </select>
                    </div>
                    <div class="form-group-inline">
                        <div class="form-group date-group">
                            <label for="appointment_date">Date</label>
                            <input type="date" id="appointment_date" name="appointment_date" required>
                        </div>
                        <div class="form-group time-group">
                            <label for="appointment_time">Time</label>
                            <input type="time" id="appointment_time" name="appointment_time" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="appointment_type">Appointment Type</label>
                        <select id="appointment_type" name="appointment_type" required>
                            <option value="">Select type</option>
                            <option value="Consultation">Consultation</option>
                            <option value="Follow-up">Follow-up</option>
                            <option value="Regular Checkup">Regular Checkup</option>
                            <option value="Emergency">Emergency</option>
                        </select>
                    </div>
                    <div id="appointment-message" class="error-message"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelSchedule">Cancel</button>
                    <button type="submit" class="btn btn-primary">Schedule</button>
                </div>
            </form>
        </div>
    </div>


    <script src="js/main.js"></script>
    <script src="js/appointments.js"></script> 
</body>
</html>