<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

require '../phpmailer/src/Exception.php';
require '../phpmailer/src/PHPMailer.php';
require '../phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include 'config.php';

$action = $_GET['action'] ?? '';


function formatPHNumber($number) {
    $number = preg_replace('/\D/', '', $number);
    if (substr($number, 0, 1) === '0') {
        $number = '+63' . substr($number, 1);
    } elseif (substr($number, 0, 2) === '63') {
        $number = '+' . $number;
    } elseif (substr($number, 0, 1) !== '+') {
        $number = '+' . $number;
    }
    return $number;
}


function sendSMS($recipient, $message) {
    
    $recipient = preg_replace('/\D/', '', $recipient);
    if (substr($recipient, 0, 1) === '0') {
        $recipient = '63' . substr($recipient, 1);
    }
    
    
    $api_token = "07bfb02b9684346bb501f8a0e5c0a3c4064028f3";

    $url = "https://sms.iprogtech.com/api/v1/sms_messages";

    $data = [
        "api_token"    => $api_token,
        "phone_number" => $recipient,
        "message"      => $message
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_HTTPHEADER     => ["Content-Type: application/x-www-form-urlencoded"],
        CURLOPT_TIMEOUT        => 30
    ]);

    $response = curl_exec($ch);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("SMS send error: $error");
        return false;
    }

    error_log("SMS API response: $response");
    $result = json_decode($response, true);
    return (isset($result['status']) && $result['status'] === 200) || (isset($result['status']) && strtolower($result['status']) === 'success');
}



if ($action === 'send') {
    $type = $_POST['type'] ?? '';
    $recipientInput = $_POST['recipient'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';

    if (!$type || !$recipientInput || !$message) {
        echo json_encode(['success' => false, 'message' => 'Required fields missing']);
        exit;
    }

    $recipients = array_map('trim', explode(',', $recipientInput));
    $successCount = 0;
    $failCount = 0;

    foreach ($recipients as $recipient) {
        try {
            if ($type === 'email') {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'fiora15.elise16@gmail.com';
                $mail->Password = 'tfde wbuv xkvp izbn';
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('fiora15.elise16@gmail.com', 'MediVault');
                $mail->addAddress($recipient);
                $mail->isHTML(true);
                $mail->Subject = $subject ?: 'MediVault Notification';
                $mail->Body = $message;
                $mail->send();
                $status = 'sent';
                $successCount++;
            } elseif ($type === 'sms') {
                $sent = sendSMS($recipient, $message);
                $status = $sent ? 'sent' : 'failed';
                if ($sent) $successCount++; else $failCount++;
            }

            $stmt = $pdo->prepare("INSERT INTO notifications (type, recipient, message, channel, status, sent_at, created_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([$type, $recipient, $message, $type, $status]);

        } catch (Exception $e) {
            $status = 'failed';
            $failCount++;
            error_log("Notification send error: " . $e->getMessage());
        }
    }

    $overallSuccess = $successCount > 0 && $failCount === 0;
    echo json_encode([
        'success' => $overallSuccess,
        'sent' => $successCount,
        'failed' => $failCount
    ]);
    exit;


} elseif ($action === 'get_history') {
    try {
        $stmt = $pdo->query("SELECT type, recipient, message, channel, status, sent_at FROM notifications ORDER BY sent_at DESC LIMIT 50");
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($notifications as $row) {
            $recipientType = detectRecipientType($row['recipient']);
            $result[] = [
                'type' => $row['type'],
                'recipient' => $row['recipient'],
                'recipient_type' => $recipientType,
                'title' => $row['type'] === 'email' ? 'Email Message' : 'SMS Message',
                'body' => $row['message'],
                'status' => $row['status'],
                'time_ago' => timeAgo(strtotime($row['sent_at'])),
                'sent_at' => $row['sent_at']
            ];
        }

        echo json_encode(['success' => true, 'notifications' => $result]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to load history: ' . $e->getMessage()]);
    }
    exit;


} elseif ($action === 'update_setting') {
    $settingId = $_POST['setting_id'] ?? '';
    $isEnabled = $_POST['is_enabled'] ?? '';
    if ($settingId && ($isEnabled === '0' || $isEnabled === '1')) {
        try {
            $stmt = $pdo->prepare("UPDATE notification_settings SET is_enabled = ? WHERE setting_id = ?");
            $stmt->execute([$isEnabled, $settingId]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to update setting: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid setting data']);
    }
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}


function timeAgo($timestamp) {
    $now = time();
    if ($timestamp > $now) $timestamp = $now;
    $diff = $now - $timestamp;
    if ($diff < 60) return $diff . ' seconds ago';
    $diff = floor($diff / 60);
    if ($diff < 60) return $diff . ' minutes ago';
    $diff = floor($diff / 60);
    if ($diff < 24) return $diff . ' hours ago';
    $diff = floor($diff / 24);
    if ($diff < 7) return $diff . ' days ago';
    return date('M j, Y', $timestamp);
}

function detectRecipientType($recipient) {
    if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) return 'patient';
    elseif (preg_match('/^\+?\d{10,15}$/', $recipient)) return 'patient family';
    else return 'staff';
}
?>
