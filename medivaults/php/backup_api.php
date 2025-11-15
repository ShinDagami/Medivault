<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? null;
$response = ['success' => false, 'message' => 'Invalid action.'];

if (!isset($pdo) || !$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

$backupDir = realpath(__DIR__ . '/../backups') ?: __DIR__ . '/../backups';
if (!is_dir($backupDir)) mkdir($backupDir, 0777, true);

$dbHost = $host;
$dbUser = $user;
$dbPass = $pass;
$dbName = $db;

$mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
$mysqlExe = 'C:\\xampp\\mysql\\bin\\mysql.exe';

function logBackup(PDO $pdo, string $status, string $filename, float $sizeMB = 0, string $filepath = '') {
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO backups (filename, type, size_mb, status, created_by, created_at, file_path)
             VALUES (?, 'manual', ?, ?, ?, NOW(), ?)"
        );
        $stmt->execute([
            $filename,
            $sizeMB,
            $status,
            $_SESSION['user_id'] ?? 0,
            $filepath
        ]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        file_put_contents(__DIR__ . '/backup_debug.log', date('Y-m-d H:i:s') . " - DB insert failed: " . $e->getMessage() . "\n", FILE_APPEND);
        return false;
    }
}

switch ($action) {
    case 'create':
        $timestamp = date('Ymd_His');
        $filename = "backup_{$timestamp}.sql";
        $filepath = "$backupDir/$filename";

        $passwordPart = $dbPass !== '' ? "-p{$dbPass}" : '';
        $command = "\"{$mysqldump}\" -h{$dbHost} -u{$dbUser} {$passwordPart} {$dbName} > \"$filepath\" 2>&1";

        exec($command, $output, $result);

        if ($result === 0 && file_exists($filepath) && filesize($filepath) > 0) {
            $sizeMB = round(filesize($filepath) / (1024 * 1024), 2);
            $insertId = logBackup($pdo, 'success', $filename, $sizeMB, $filepath);

            if ($insertId) {
                $backupRow = $pdo->query("SELECT * FROM backups WHERE id = $insertId")->fetch(PDO::FETCH_ASSOC);
                $response = [
                    'success' => true,
                    'message' => 'Database backup created successfully!',
                    'backup' => $backupRow
                ];
            } else {
                $response = [
                    'success' => true,
                    'message' => 'Backup created successfully, but failed to log in database.',
                    'backup' => [
                        'created_by' => $_SESSION['user_id'] ?? 0,
                        'type' => 'manual',
                        'created_at' => date('Y-m-d H:i:s'),
                        'status' => 'warning',
                        'size_mb' => $sizeMB,
                        'file_path' => $filepath,
                        'filename' => $filename
                    ]
                ];
            }
        } else {
            logBackup($pdo, 'failed', $filename);
            file_put_contents(__DIR__ . '/backup_debug.log', date('Y-m-d H:i:s') . " - Backup failed\nCommand: $command\nOutput: " . implode("\n", $output) . "\n\n", FILE_APPEND);

            $response = [
                'success' => false,
                'message' => 'Backup creation failed. Check backup_debug.log for details.',
                'debug' => $output
            ];
        }
        break;

    case 'restore':
        $file_to_restore = $_POST['filename'] ?? null;
        $filepath = "$backupDir/$file_to_restore";

        if (!$file_to_restore || !file_exists($filepath)) {
            $response = ['success' => false, 'message' => 'Invalid or missing backup file specified.'];
            break;
        }

        $passwordPart = $dbPass !== '' ? "-p{$dbPass}" : '';
        $command = "\"{$mysqlExe}\" -h{$dbHost} -u{$dbUser} {$passwordPart} {$dbName} < \"$filepath\" 2>&1";

        exec($command, $output, $result);

        if ($result === 0) {
            $response = ['success' => true, 'message' => 'Database restoration successful.'];
        } else {
            file_put_contents(__DIR__ . '/backup_debug.log', date('Y-m-d H:i:s') . " - Restore failed\nCommand: $command\nOutput: " . implode("\n", $output) . "\n\n", FILE_APPEND);
            $response = ['success' => false, 'message' => 'Database restoration failed. Check backup_debug.log for details.'];
        }
        break;

    case 'download':
        $file_to_download = $_GET['filename'] ?? null;

        if ($file_to_download === 'latest') {
            $stmt = $pdo->query("SELECT file_path FROM backups WHERE status='success' ORDER BY created_at DESC LIMIT 1");
            $latest = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($latest && file_exists($latest['file_path'])) {
                $filepath = $latest['file_path'];
                $file_to_download = basename($filepath);
            } else {
                echo json_encode(['success' => false, 'message' => 'No valid latest backup found.']);
                exit;
            }
        } else {
            $filepath = "$backupDir/$file_to_download";
            if (!file_exists($filepath)) {
                echo json_encode(['success' => false, 'message' => 'Backup file not found.']);
                exit;
            }
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file_to_download . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        ob_clean();
        flush();
        readfile($filepath);
        exit;
        break;

    case 'list':
        try {
            $stmt = $pdo->query("SELECT * FROM backups ORDER BY created_at DESC LIMIT 5");
            $backups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response = ['success' => true, 'backups' => $backups];
        } catch (PDOException $e) {
            $response = ['success' => false, 'message' => $e->getMessage()];
        }
        break;
}

echo json_encode($response);
?>
