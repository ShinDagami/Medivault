<?php
session_start();
include 'config.php';
include 'audit_util.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in again.']); 
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'fetch_patients':
        fetchPatients($pdo);
        break;
    case 'fetch_doctors':
        fetchDoctors($pdo);
        break;
    case 'schedule_appointment':
        scheduleAppointment($pdo);
        break;
    case 'fetch_appointments_by_date':
        fetchAppointmentsByDate($pdo);
        break;
    case 'update_appointment_status':
        updateAppointmentStatus($pdo);
        break;
    case 'fetch_current_queue':
        fetchCurrentQueue($pdo);
        break;
    case 'delete_appointment':
        deleteAppointment($pdo); 
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}



function fetchPatients($pdo) {
    try {
        $stmt = $pdo->query("SELECT patient_id, name FROM patients WHERE status = 'Active' ORDER BY name");
        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $patients]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function fetchDoctors($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT id, name FROM staff WHERE role = 'Doctor' AND status = 'Active' ORDER BY name");
        $stmt->execute();
        $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $doctors]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function scheduleAppointment($pdo) {
    $patient_id = $_POST['patient_id'] ?? null;
    $doctor_id = $_POST['doctor_id'] ?? null;
    $date = $_POST['appointment_date'] ?? null;
    $time = $_POST['appointment_time'] ?? null;
    $type = $_POST['appointment_type'] ?? null;
    $status = 'Pending';

    if (!$patient_id || !$doctor_id || !$date || !$time || !$type) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
        return;
    }

    try {
        $datetime = $date . ' ' . $time . ':00';
        $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_datetime, type, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$patient_id, $doctor_id, $datetime, $type, $status]);

        
        $pStmt = $pdo->prepare("SELECT name FROM patients WHERE patient_id = ?");
        $pStmt->execute([$patient_id]);
        $patient_name = $pStmt->fetchColumn() ?? "Unknown Patient";

        $dStmt = $pdo->prepare("SELECT name, role FROM staff WHERE id = ?");
        $dStmt->execute([$doctor_id]);
        $doctor = $dStmt->fetch(PDO::FETCH_ASSOC);
        $doctor_name = $doctor['name'] ?? "Unknown Doctor";
        $doctor_role = $doctor['role'] ?? "Unknown Role";

        
        $details = "Scheduled appointment for patient {$patient_name} with {$doctor_role} {$doctor_name} at {$datetime}, type={$type}";
        log_audit_action($pdo, 'Add', 'Appointments', $details);

        echo json_encode(['success' => true, 'message' => 'Appointment scheduled successfully.']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to schedule appointment. DB Error: ' . $e->getMessage()]);
    }
}

function fetchAppointmentsByDate($pdo) {
    $selected_date = $_GET['date'] ?? date('Y-m-d');

    try {
        $sql = "SELECT 
              a.id AS appointment_id,
              p.name AS patient_name, 
              d.name AS doctor_name, 
              a.type, 
              a.status, 
              TIME_FORMAT(a.appointment_datetime, '%h:%i %p') AS time_only,
              DATE(a.appointment_datetime) as appointment_date
            FROM 
              appointments a
            JOIN 
              patients p ON a.patient_id = p.patient_id
            JOIN 
              staff d ON a.doctor_id = d.id 
            WHERE 
              DATE(a.appointment_datetime) = ?
            ORDER BY 
              a.appointment_datetime ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$selected_date]);
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $appointments]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function updateAppointmentStatus($pdo) {
    $appointment_id = $_POST['appointment_id'] ?? null;
    $new_status = $_POST['status'] ?? null;

    if (!$appointment_id || !$new_status) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing appointment ID or status']);
        return;
    }

    $normalized_status = strtolower(trim($new_status));
    $normalized_status = str_replace(['-', ' '], '', $normalized_status);
    if ($normalized_status === 'pending') $new_status = 'Pending';
    elseif ($normalized_status === 'ongoing') $new_status = 'Ongoing';
    elseif ($normalized_status === 'completed') $new_status = 'Completed';
    elseif ($normalized_status === 'cancelled') $new_status = 'Cancelled';
    else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status received']);
        return;
    }

    try {
        
        $stmtInfo = $pdo->prepare("
            SELECT a.patient_id, a.doctor_id, p.name AS patient_name, s.name AS doctor_name, s.role AS doctor_role
            FROM appointments a
            JOIN patients p ON a.patient_id = p.patient_id
            JOIN staff s ON a.doctor_id = s.id
            WHERE a.id = ?
        ");
        $stmtInfo->execute([$appointment_id]);
        $appt = $stmtInfo->fetch(PDO::FETCH_ASSOC);

        $patient_name = $appt['patient_name'] ?? 'Unknown Patient';
        $doctor_name = $appt['doctor_name'] ?? 'Unknown Doctor';
        $doctor_role = $appt['doctor_role'] ?? 'Unknown Role';

        
        $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $appointment_id]);

        
        $details = "Updated appointment for patient {$patient_name} with {$doctor_role} {$doctor_name} to status={$new_status}";
        log_audit_action($pdo, 'Edit', 'Appointments', $details);

        echo json_encode(['success' => true, 'message' => 'Status updated']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
    }
}

function fetchCurrentQueue($pdo) {
    try {
        $sql = "SELECT 
           a.id AS appointment_id,
           p.name AS patient_name, 
           d.name AS doctor_name, 
           a.status
          FROM 
           appointments a
          JOIN 
           patients p ON a.patient_id = p.patient_id
          JOIN 
           staff d ON a.doctor_id = d.id
          WHERE 
           DATE(a.appointment_datetime) = CURDATE() AND a.status IN ('Pending', 'Ongoing')
          ORDER BY 
           a.appointment_datetime ASC
          LIMIT 3";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(); 
        $queue = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($queue)) {
            foreach ($queue as $i => &$app) {
                if ($app['status'] === 'Ongoing') $app['display_status'] = 'Being Attended';
                elseif ($app['status'] === 'Pending') $app['display_status'] = 'Waiting';
                else $app['display_status'] = $app['status'];
            }
            unset($app);

            echo json_encode(['success' => true, 'data' => $queue]);
        } else {
            echo json_encode(['success' => true, 'data' => []]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function deleteAppointment($pdo) {
    $appointment_id = $_POST['appointment_id'] ?? null;

    if (!$appointment_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing appointment ID for deletion.']);
        return;
    }

    try {
        
        $stmtInfo = $pdo->prepare("
            SELECT a.patient_id, a.doctor_id, p.name AS patient_name, s.name AS doctor_name, s.role AS doctor_role
            FROM appointments a
            JOIN patients p ON a.patient_id = p.patient_id
            JOIN staff s ON a.doctor_id = s.id
            WHERE a.id = ?
        ");
        $stmtInfo->execute([$appointment_id]);
        $appt = $stmtInfo->fetch(PDO::FETCH_ASSOC);

        $patient_name = $appt['patient_name'] ?? 'Unknown Patient';
        $doctor_name = $appt['doctor_name'] ?? 'Unknown Doctor';
        $doctor_role = $appt['doctor_role'] ?? 'Unknown Role';

        $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ?");
        $stmt->execute([$appointment_id]);

        if ($stmt->rowCount() > 0) {
            $details = "Deleted appointment for patient {$patient_name} with {$doctor_role} {$doctor_name}";
            log_audit_action($pdo, 'Delete', 'Appointments', $details);

            echo json_encode(['success' => true, 'message' => 'Appointment deleted successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Appointment not found or already deleted.']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete appointment. DB Error: ' . $e->getMessage()]);
    }
}
?>
