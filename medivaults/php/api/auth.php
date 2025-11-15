<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

ob_start();


header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
session_start();
header('Content-Type: application/json');


include '../config.php';

$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');
$password = trim($input['password'] ?? '');
$tab = trim($input['role'] ?? 'staff'); 

if ($username === '' || $password === '') {
    echo json_encode(['ok' => false, 'error' => 'Username and password required']);
    exit;
}


if (!isset($pdo)) {
    
    echo json_encode(['ok' => false, 'error' => 'Database connection error.']);
    exit;
}

try {
    
    $stmt = $pdo->prepare("SELECT * FROM staff WHERE username = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Login DB Error: " . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Server error during login.']);
    exit;
}


if (!$user) {
    echo json_encode(['ok' => false, 'error' => 'Invalid credentials']);
    exit;
}




if (!password_verify($password, $user['password'])) {
    echo json_encode(['ok' => false, 'error' => 'Invalid credentials']);
    exit;
}


$role = strtolower(trim($user['role']));
if ($tab === 'admin' && $role !== 'admin') {
    echo json_encode(['ok' => false, 'error' => 'Access denied for this tab']);
    exit;
}

if ($tab === 'staff' && $role === 'admin') {
    echo json_encode(['ok' => false, 'error' => 'Access denied for this tab']);
    exit;
}


function formatDisplayName($fullName, $role) {
    $nameParts = preg_split('/\s+/', trim($fullName));
    $prefix = '';

    switch (strtolower($role)) {
        case 'doctor':
            $prefix = 'Dr.';
            break;
        case 'nurse':
            $prefix = 'Nurse';
            break;
        case 'lab technician':
            $prefix = 'LT';
            break;
        case 'pharmacist':
            $prefix = 'RPh';
            break;
    }

    $lastName = end($nameParts);
    $initials = '';

    
    if (count($nameParts) > 2) {
        for ($i = 0; $i < count($nameParts) - 1; $i++) {
            $word = $nameParts[$i];
            if (preg_match('/^[A-Za-z]/', $word)) {
                $initials .= strtoupper($word[0]) . '. ';
            }
        }
        return trim("$prefix $initials$lastName");
    }

    
    if (count($nameParts) == 2) {
        $firstInitial = strtoupper($nameParts[0][0]);
        return trim("$prefix $firstInitial. $lastName");
    }

    
    return trim("$prefix {$nameParts[0]}");
}

$displayName = formatDisplayName($user['name'], $user['role']);


$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];
$_SESSION['displayName'] = $displayName;

$_SESSION['access_level'] = $user['access_level'] ?? null; 

ob_clean();
echo json_encode(['ok' => true, 'displayName' => $displayName]);
exit;
?>