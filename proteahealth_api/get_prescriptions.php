<?php

header("Content-Type: application/json; charset=utf-8");
require_once "db.php";

$patient_id = intval($_GET['patient_id'] ?? $_GET['patientId'] ?? 0);

try {
    if ($patient_id > 0) {
        $stmt = $conn->prepare("
            SELECT
                p.id,
                p.id AS prescription_id,
                p.patient_id,
                p.doctor_id,
                p.medication_id,
                COALESCE(pm.medication_name, p.dosage, 'Medication') AS medicationName,
                p.quantity,
                CONCAT('Dr. ', COALESCE(d.name, 'Doctor'), ' ', COALESCE(d.surname, '')) AS prescribingDoctor,
                COALESCE(d.email, 'Contact Doctor') AS doctorContact,
                COALESCE(p.dosage, '1 dose') AS dosage,
                'Tablet/Capsule' AS appearance,
                COALESCE(p.purpose, 'Prescribed Medication') AS purpose,
                COALESCE(p.scheduled_time, '08:00 AM') AS scheduledTime,
                p.quantity AS totalDoses,
                p.remaining_doses AS remainingDoses,
                IF(p.last_taken_date = CURDATE(), 1, 0) AS takenToday
            FROM prescriptions p
            LEFT JOIN Doctor d ON d.id = p.doctor_id
            LEFT JOIN Pharmacy_Medication pm ON pm.id = p.medication_id
            WHERE p.patient_id = ?
            ORDER BY p.date_prescription DESC
        ");
        $stmt->execute([$patient_id]);
    } else {
        $stmt = $conn->prepare("
            SELECT
                p.id,
                p.id AS prescription_id,
                p.patient_id,
                p.doctor_id,
                p.medication_id,
                COALESCE(pm.medication_name, p.dosage, 'Medication') AS medicationName,
                p.quantity,
                CONCAT('Dr. ', COALESCE(d.name, 'Doctor'), ' ', COALESCE(d.surname, '')) AS prescribingDoctor,
                COALESCE(d.email, 'Contact Doctor') AS doctorContact,
                COALESCE(p.dosage, '1 dose') AS dosage,
                'Tablet/Capsule' AS appearance,
                COALESCE(p.purpose, 'Prescribed Medication') AS purpose,
                COALESCE(p.scheduled_time, '08:00 AM') AS scheduledTime,
                p.quantity AS totalDoses,
                p.remaining_doses AS remainingDoses,
                IF(p.last_taken_date = CURDATE(), 1, 0) AS takenToday
            FROM prescriptions p
            LEFT JOIN Doctor d ON d.id = p.doctor_id
            LEFT JOIN Pharmacy_Medication pm ON pm.id = p.medication_id
            ORDER BY p.date_prescription DESC
        ");
        $stmt->execute();
    }

    $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($prescriptions as &$p) {
        $p['id'] = (string)$p['id'];
        $p['quantity'] = (int)$p['quantity'];
        $p['totalDoses'] = (int)$p['totalDoses'];
        $p['remainingDoses'] = (int)$p['remainingDoses'];
        $p['takenToday'] = (bool)$p['takenToday'];
    }

    echo json_encode($prescriptions);

} catch (Exception $e) {
    echo json_encode([]);
}

?>