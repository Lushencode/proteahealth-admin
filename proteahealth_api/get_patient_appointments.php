<?php



header("Content-Type: application/json");

require_once "db.php";

$patient_id = isset($_GET["patient_id"])
    ? intval($_GET["patient_id"])
    : 0;

if ($patient_id <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Patient ID is required",
        "appointments" => []
    ]);

    exit;
}

try {

    $stmt = $conn->prepare("
        SELECT
            a.id,
            a.patient_id,
            a.doctor_id,
            a.appointment_type,
            a.appointment_date,
            a.appointment_time,
            a.reason,
            a.accepted,
            a.status,
            a.doctor_response,

            d.name AS doctor_name,
            d.surname AS doctor_surname,
            d.specialization,
            d.clinic_name,
            d.location

        FROM Appointments a

        INNER JOIN Doctor d
            ON a.doctor_id = d.id

        WHERE a.patient_id = ?

        ORDER BY
            a.appointment_date DESC,
            a.appointment_time DESC
    ");

    $stmt->execute([$patient_id]);

    $appointments =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "message" => "Appointments loaded successfully",
        "appointments" => $appointments
    ]);

} catch (PDOException $e) {

    echo json_encode([
        "success" => false,
        "message" => "Unable to load appointments",
        "appointments" => []
    ]);
}

?>