<?php
header("Content-Type: application/json; charset=utf-8");
require_once "db.php";

$doctor_id = intval($_GET['doctor_id'] ?? $_GET['provider_id'] ?? 0);

try {
    if ($doctor_id > 0) {
        $stmt = $conn->prepare("
            SELECT
                a.id,
                a.id AS appointment_id,
                a.patient_id,
                a.doctor_id,
                CONCAT(p.name, ' ', p.surname) AS patientName,
                p.phone AS patientPhone,
                a.appointment_type AS appointmentType,
                a.appointment_date AS appointmentDate,
                a.appointment_time AS appointmentTime,
                a.reason,
                a.status,
                COALESCE(a.doctor_response, '') AS doctorResponse
            FROM Appointments a
            LEFT JOIN Patient p ON p.id = a.patient_id
            WHERE a.doctor_id = ?
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
        ");
        $stmt->execute([$doctor_id]);
    } else {
        $stmt = $conn->prepare("
            SELECT
                a.id,
                a.id AS appointment_id,
                a.patient_id,
                a.doctor_id,
                CONCAT(p.name, ' ', p.surname) AS patientName,
                p.phone AS patientPhone,
                a.appointment_type AS appointmentType,
                a.appointment_date AS appointmentDate,
                a.appointment_time AS appointmentTime,
                a.reason,
                a.status,
                COALESCE(a.doctor_response, '') AS doctorResponse
            FROM Appointments a
            LEFT JOIN Patient p ON p.id = a.patient_id
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
        ");
        $stmt->execute();
    }

    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "appointments" => $appointments
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error fetching appointments: " . $e->getMessage()
    ]);
}
?>