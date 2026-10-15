
<?php

header("Content-Type: application/json; charset=utf-8");

require_once "db.php";

$doctor_id = intval($_GET["doctor_id"] ?? 0);
$date = trim($_GET["date"] ?? "");

if ($doctor_id <= 0 || empty($date)) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Doctor ID and date are required",
        "appointments" => []
    ]);

    exit;
}

$dateObject = DateTimeImmutable::createFromFormat(
    "!Y-m-d",
    $date
);

if (!$dateObject || $dateObject->format("Y-m-d") !== $date) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid date format",
        "appointments" => []
    ]);

    exit;
}

try {

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

        LEFT JOIN Patient p
            ON p.id = a.patient_id

        WHERE a.doctor_id = ?
          AND a.appointment_date = ?

        ORDER BY a.appointment_time ASC
    ");

    $stmt->execute([
        $doctor_id,
        $date
    ]);

    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "doctor_id" => $doctor_id,
        "date" => $date,
        "appointments" => $appointments
    ]);

} catch (Throwable $e) {

    error_log(
        "Doctor appointments error: " . $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to fetch appointments",
        "appointments" => []
    ]);
}
?>
