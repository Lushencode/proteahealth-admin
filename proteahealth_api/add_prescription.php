<?php
header("Content-Type: application/json; charset=utf-8");
require_once "db.php";

$raw_input = file_get_contents("php://input");
$input = json_decode($raw_input, true);

$patient_id = intval($input['patientId'] ?? $input['patient_id'] ?? 0);
$doctor_id = intval($input['doctorId'] ?? $input['doctor_id'] ?? 0);
$medication = trim($input['medication'] ?? '');
$dosage = trim($input['dosage'] ?? '');
$purpose = trim($input['purpose'] ?? 'Prescribed Medication');
$scheduled_time = trim($input['scheduledTime'] ?? $input['scheduled_time'] ?? '08:00:00');
$quantity = intval($input['quantity'] ?? 30);

if ($patient_id <= 0 || $doctor_id <= 0 || empty($medication)) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

try {
    // Check if medication exists in Pharmacy_Medication or use default ID 1
    $med_stmt = $conn->prepare("SELECT id FROM Pharmacy_Medication WHERE medication_name LIKE ? LIMIT 1");
    $med_stmt->execute(["%" . $medication . "%"]);
    $med_row = $med_stmt->fetch(PDO::FETCH_ASSOC);
    $medication_id = $med_row ? intval($med_row['id']) : 1;

    $stmt = $conn->prepare("
        INSERT INTO prescriptions (
            patient_id,
            doctor_id,
            medication_id,
            quantity,
            dosage,
            purpose,
            scheduled_time,
            remaining_doses,
            date_prescription
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $patient_id,
        $doctor_id,
        $medication_id,
        $quantity,
        $dosage,
        $purpose,
        $scheduled_time,
        $quantity
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Prescription added successfully",
        "prescription_id" => $conn->lastInsertId()
    ]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>