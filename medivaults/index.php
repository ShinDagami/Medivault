<?php
session_start();
if (isset($_SESSION['username'])) {
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediVault - Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <main class="login-wrapper">
        <img src="assets/images/logonga.PNG" alt="MediVault logo" class="logo">

        <div class="tab-switch" role="tablist" aria-label="Login type">
            <button class="tab active" data-role="staff" role="tab" aria-selected="true">Staff Login</button>
            <button class="tab" data-role="admin" role="tab" aria-selected="false">Admin Login</button>
        </div>

        <form id="loginForm" class="login-card" novalidate>
            <h2 id="formTitle">Staff Login</h2>
            <p class="desc">Enter your credentials to access the system</p>

            <label class="field">
                <span class="label-text">Username</span>
                <input id="username" name="username" type="text" placeholder="Enter username" autocomplete="username">
            </label>

            <label class="field">
                <span class="label-text">Password</span>
                <input id="password" name="password" type="password" placeholder="Enter password" autocomplete="current-password">
            </label>

            <div class="form-bottom">
                <a href="#" id="forgot" class="forgot">Forgot Password?</a>
            </div>

            <div class="actions">
                <button id="submitBtn" class="btn primary" type="submit">Login as Staff</button>
            </div>

            <div id="error" class="error" aria-live="polite"></div>
        </form>
    </main>

    <script src="js/script.js"></script>
    
</body>
</html>
