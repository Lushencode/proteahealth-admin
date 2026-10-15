<?php
header("Content-Type: application/json; charset=utf-8");
require_once "db.php";

$raw_input = file_get_contents("php://input");
$input = json_decode($raw_input, true) ?: [];

$prescription_id = intval($input['prescription_id'] ?? $input['id'] ?? $_POST['prescription_id'] ?? $_POST['id'] ?? 0);
$medication = trim($input['medication'] ?? $_POST['medication'] ?? '');
$dosage = trim($input['dosage'] ?? $_POST['dosage'] ?? '');
$purpose = trim($input['purpose'] ?? $_POST['purpose'] ?? '');
$quantity = intval($input['quantity'] ?? $_POST['quantity'] ?? 30);

if ($prescription_id <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid prescription ID"]);
    exit;
}

try {
    $stmt = $conn->prepare("
        UPDATE prescriptions SET
            dosage = ?,
            purpose = ?,
            quantity = ?,
            remaining_doses = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $dosage,
        !empty($purpose) ? $purpose : $medication,
        $quantity,
        $quantity,
        $prescription_id
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Prescription updated successfully"
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error updating prescription: " . $e->getMessage()
    ]);
}
?>