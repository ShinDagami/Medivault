<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../audit_util.php'; 

if (!isset($pdo)) {
    http_response_code(500);
    error_log("FATAL: Database connection (PDO) not available.");
    die(json_encode(["success" => false, "message" => "Server initialization error. Check config.php."]));
}

header('Content-Type: application/json');
define('UPLOAD_DIR', __DIR__ . '/../../uploads/faces/');


function generate_patient_id() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT MAX(id) AS max_id FROM patients");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $next_id = ($row['max_id'] ?? 0) + 1;
        return 'P' . str_pad($next_id, 4, '0', STR_PAD_LEFT);
    } catch (PDOException $e) {
        error_log("SQL Error in generate_patient_id: " . $e->getMessage());
        return false;
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    global $pdo;

    $required = ['name', 'age', 'gender'];
    foreach ($required as $f) {
        if (empty($data[$f])) {
            http_response_code(400);
            die(json_encode(["success" => false, "message" => "Missing field: " . $f]));
        }
    }

    $patient_id = generate_patient_id();
    if (!$patient_id) {
        http_response_code(500);
        die(json_encode(["success" => false, "message" => "Failed to generate patient ID."]));
    }

    $name = sanitizeInput($data['name']);
    $age = (int)$data['age'];
    $gender = sanitizeInput($data['gender']);
    $contact = sanitizeInput($data['contact'] ?? '');
    $address = sanitizeInput($data['address'] ?? '');
    $blood_type = sanitizeInput($data['blood_type'] ?? '');
    $emergency_contact = sanitizeInput($data['emergency_contact'] ?? '');
    $medical_history = sanitizeInput($data['medical_history'] ?? '');
    $allergies = sanitizeInput($data['allergies'] ?? '');
    $chronic_conditions = sanitizeInput($data['chronic_conditions'] ?? 'None');
    $last_visit = sanitizeInput($data['last_visit'] ?? '');
    $face_descriptor_json = $data['face_descriptor'] ?? null;
    $face_image_base64 = $data['face_image_base64'] ?? null;
    $biometric_enrolled = !empty($face_descriptor_json) ? 1 : 0;
    $face_image_path = null;

    if ($biometric_enrolled && $face_image_base64) {
        $img_data = explode(',', $face_image_base64);
        if (count($img_data) == 2) {
            $binary_data = base64_decode($img_data[1]);
            if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0777, true);
            $filename = 'face_' . $patient_id . '_' . time() . '.jpeg';
            $path = UPLOAD_DIR . $filename;
            if (file_put_contents($path, $binary_data)) {
                $face_image_path = 'uploads/faces/' . $filename;
            } else {
                error_log("Failed to save image file: " . $path);
                $biometric_enrolled = 0;
                $face_descriptor_json = null;
            }
        }
    }

    $sql = "INSERT INTO patients (
        patient_id, name, age, gender, contact, address, blood_type,
        emergency_contact, medical_history, allergies, chronic_conditions,
        face_image_path, face_encoding, biometric_enrolled, last_visit
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $params = [
        $patient_id, $name, $age, $gender, $contact, $address, $blood_type,
        $emergency_contact, $medical_history, $allergies, $chronic_conditions,
        $face_image_path, $face_descriptor_json, $biometric_enrolled, $last_visit
    ];

    if ($stmt->execute($params)) {
        log_audit_action($pdo, 'Add', 'Patient', "Registered new patient $patient_id - $name");
        echo json_encode(["success" => true, "message" => "Patient $patient_id registered successfully."]);
    } else {
        http_response_code(500);
        error_log("Execute failed: " . print_r($stmt->errorInfo(), true));
        echo json_encode(["success" => false, "message" => "Insert failed."]);
    }
}


elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    global $pdo;
    $action = $_GET['action'] ?? '';
    $id = $_GET['id'] ?? '';

    if ($action === 'read' && !empty($id)) {
        $sql = "SELECT id, patient_id, name, age, gender, contact, address, blood_type,
                 emergency_contact, medical_history, allergies, chronic_conditions,
                 last_visit, status, face_image_path, face_encoding, biometric_enrolled,
                 created_at AS registration_date
                 FROM patients WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            log_audit_action($pdo, 'View', 'Patient', "Viewed patient {$data['patient_id']} - {$data['name']}");
            echo json_encode(["success" => true, "data" => $data]);
        } else {
            echo json_encode(["success" => false, "message" => "Patient not found."]);
        }

    } else {
        $sql = "SELECT id, patient_id, name, age, gender, contact, status
                 FROM patients ORDER BY created_at DESC";
        $stmt = $pdo->query($sql);
        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "data" => $patients]);
    }
}


elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    global $pdo;
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Missing patient ID."]);
        exit;
    }

    $id = (int)$data['id'];
    $stmt = $pdo->prepare("SELECT face_image_path, face_encoding FROM patients WHERE id = ?");
    $stmt->execute([$id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    $existing_face_path = $existing['face_image_path'];
    $existing_face_encoding = $existing['face_encoding'];

    $name = sanitizeInput($data['name'] ?? '');
    $age = (int)($data['age'] ?? 0);
    $gender = sanitizeInput($data['gender'] ?? '');
    $contact = sanitizeInput($data['contact'] ?? '');
    $address = sanitizeInput($data['address'] ?? '');
    $blood_type = sanitizeInput($data['blood_type'] ?? '');
    $emergency_contact = sanitizeInput($data['emergency_contact'] ?? '');
    $medical_history = sanitizeInput($data['medical_history'] ?? '');
    $allergies = sanitizeInput($data['allergies'] ?? '');
    $chronic_conditions = sanitizeInput($data['chronic_conditions'] ?? 'None');
    $last_visit = sanitizeInput($data['last_visit'] ?? '');
    $status = sanitizeInput($data['status'] ?? 'Active');

    $face_image_path = !empty($data['face_image_path']) ? $data['face_image_path'] : $existing_face_path;
    $face_encoding = !empty($data['face_encoding']) ? $data['face_encoding'] : $existing_face_encoding;
    $biometric_enrolled = !empty($face_encoding) ? 1 : 0;

    $sql = "UPDATE patients SET
        name=?, age=?, gender=?, contact=?, address=?, blood_type=?, 
        emergency_contact=?, medical_history=?, allergies=?, chronic_conditions=?, 
        last_visit=?, status=?, face_image_path=?, face_encoding=?, biometric_enrolled=?
        WHERE id=?";

    $stmt = $pdo->prepare($sql);
    $params = [
        $name, $age, $gender, $contact, $address, $blood_type, 
        $emergency_contact, $medical_history, $allergies, $chronic_conditions, 
        $last_visit, $status, $face_image_path, $face_encoding, 
        $biometric_enrolled, $id
    ];

    if ($stmt->execute($params)) {
        log_audit_action($pdo, 'Edit', 'Patient', "Updated patient record ID $id - $name");
        echo json_encode(["success" => true, "message" => "Patient updated successfully."]);
    } else {
        http_response_code(500);
        error_log("Update failed: " . print_r($stmt->errorInfo(), true));
        echo json_encode(["success" => false, "message" => "Update failed."]);
    }
}


elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    global $pdo;
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        die(json_encode(["success" => false, "message" => "Invalid ID."]));
    }

    $stmt_select = $pdo->prepare("SELECT face_image_path, name FROM patients WHERE id=?");
    $stmt_select->execute([$id]);
    if ($row = $stmt_select->fetch(PDO::FETCH_ASSOC)) {
        $path = __DIR__ . '/../../' . $row['face_image_path'];
        if (!empty($row['face_image_path']) && file_exists($path)) {
            unlink($path);
        }
    }

    $stmt_delete = $pdo->prepare("DELETE FROM patients WHERE id=?");
    if ($stmt_delete->execute([$id])) {
        log_audit_action($pdo, 'Delete', 'Patient', "Deleted patient ID $id - {$row['name']}");
        echo json_encode(["success" => true, "message" => "Patient deleted."]);
    } else {
        http_response_code(500);
        error_log("Delete failed: " . print_r($stmt_delete->errorInfo(), true));
        echo json_encode(["success" => false, "message" => "Delete failed."]);
    }
}
?>
