<?php
function sanitizeInput($data) {
    return htmlspecialchars(trim((string)($data ?? '')), ENT_QUOTES, 'UTF-8');
}

function is_logged_in() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

