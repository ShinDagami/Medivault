<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
session_start();
header('Content-Type: application/json');


include '../config.php';

$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');
$password = trim($input['password'] ?? '');
$role = strtolower(trim($input['role'] ?? 'staff')); 

if (!$username || !$password) {
    echo json_encode(['ok' => false, 'error' => 'Username and password required']);
    exit;
}


if (!isset($pdo)) {
    echo json_encode(['ok' => false, 'error' => 'Server error: Database connection failed.']);
    exit;
}

try {
    
    $stmt = $pdo->prepare("SELECT id, username, password, role, name, access_level FROM staff WHERE username = :username LIMIT 1");
    
    
    $stmt->execute([':username' => $username]);
    
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Auth DB Error: " . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Server error during authentication.']);
    exit;
}


if (!$user) {
    echo json_encode(['ok' => false, 'error' => 'Invalid username or role']);
    exit;
}


if (password_verify($password, $user['password'])) {
    
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['access_level'] = $user['access_level'];

    
    echo json_encode(['ok' => true, 'role' => $user['role']]);
    
} else {
    
    echo json_encode(['ok' => false, 'error' => 'Invalid password']);
}



?>