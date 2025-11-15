<?php



if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function get_staff_name() {
    if (isset($_SESSION['role']) && isset($_SESSION['username'])) {
        return $_SESSION['role'] . " (" . $_SESSION['username'] . ")";
    } elseif (isset($_SESSION['username'])) {
        return $_SESSION['username'];
    } elseif (isset($_SESSION['name'])) {
        return $_SESSION['name'];
    } else {
        return 'Unknown User';
    }
}

/**
 *
 * @param PDO $pdo
 * @param string 
 * @param string 
 * @param string 
 */
function log_audit_action($pdo, $action, $module, $details) {
    $staff_name = get_staff_name();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (staff_name, action, module, details, ip_address, timestamp)
            VALUES (:staff_name, :action, :module, :details, :ip_address, NOW())
        ");
        $stmt->execute([
            ':staff_name' => $staff_name,
            ':action'     => $action,
            ':module'     => $module,
            ':details'    => $details,
            ':ip_address' => $ip
        ]);
    } catch (PDOException $e) {
        error_log("Audit log error: " . $e->getMessage());
    }
}
?>
