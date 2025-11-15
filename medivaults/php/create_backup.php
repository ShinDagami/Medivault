<?php
session_start();
include 'config.php';
header('Content-Type: application/json');


if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}


$backupDir = __DIR__ . '/../backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

$timestamp = date('Ymd_His');
$filename = "backup_{$timestamp}.sql";
$filepath = "{$backupDir}/{$filename}";
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'medivaultdb';
$mysqldump = 'C:\xampp\mysql\bin\mysqldump.exe';


$passwordPart = $DB_PASS !== '' ? "-p{$DB_PASS}" : '';
$command = "\"{$mysqldump}\" -h{$DB_HOST} -u{$DB_USER} {$passwordPart} {$DB_NAME} > \"{$filepath}\"";


exec($command, $output, $result);


if ($result === 0 && file_exists($filepath) && filesize($filepath) > 0) {
    $sizeMB = round(filesize($filepath) / (1024 * 1024), 2);

    
    try {
        $stmt = $pdo->prepare("INSERT INTO backups (user_id, type, created_at, status, size_mb, file_path)
                               VALUES (?, 'manual', NOW(), 'success', ?, ?)");
        $stmt->execute([$_SESSION['user_id'] ?? 0, $sizeMB, $filepath]);
    } catch (PDOException $e) {
        
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Backup created successfully',
        'file' => $filename,
        'size' => "{$sizeMB} MB"
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Backup failed. Check database credentials or mysqldump path.',
        'debug' => $output
    ]);
}
?>
