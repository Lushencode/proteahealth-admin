
<?php

header("Content-Type: application/json");

require_once "db.php";

function sendJson($status, $data) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    sendJson(405, [
        "success" => false,
        "message" => "Only POST requests are allowed"
    ]);
}

$doctorId = filter_var(
    $_POST["doctor_id"] ?? null,
    FILTER_VALIDATE_INT
);

$date = trim($_POST["available_date"] ?? "");
$startTime = trim($_POST["start_time"] ?? "");
$endTime = trim($_POST["end_time"] ?? "");

$slotDuration = filter_var(
    $_POST["slot_duration"] ?? 30,
    FILTER_VALIDATE_INT
);

if (!$doctorId || $doctorId <= 0) {
    sendJson(400, [
        "success" => false,
        "message" => "Valid doctor ID is required"
    ]);
}

$dateObject = DateTimeImmutable::createFromFormat(
    "!Y-m-d",
    $date
);

if (!$dateObject || $dateObject->format("Y-m-d") !== $date) {
    sendJson(400, [
        "success" => false,
        "message" => "Invalid date format"
    ]);
}

function validTime($value) {
    return preg_match(
        "/^([01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/",
        $value
    ) === 1;
}

if (
    !validTime($startTime) ||
    !validTime($endTime) ||
    $startTime >= $endTime
) {
    sendJson(400, [
        "success" => false,
        "message" => "Invalid working hours"
    ]);
}

if (!$slotDuration || $slotDuration <= 0 || $slotDuration > 240) {
    sendJson(400, [
        "success" => false,
        "message" => "Invalid appointment duration"
    ]);
}

try {

    $conn->beginTransaction();

    // Lock the doctor record to serialize availability updates.
    $doctorStmt = $conn->prepare("
        SELECT id
        FROM Doctor
        WHERE id = ?
        FOR UPDATE
    ");

    $doctorStmt->execute([$doctorId]);

    if (!$doctorStmt->fetch(PDO::FETCH_ASSOC)) {
        $conn->rollBack();

        sendJson(404, [
            "success" => false,
            "message" => "Doctor not found"
        ]);
    }

    // Prevent changing hours in a way that excludes
    // appointments that are already booked.
    $bookedStmt = $conn->prepare("
        SELECT COUNT(*)
        FROM Appointments
        WHERE doctor_id = ?
          AND appointment_date = ?
          AND status IN ('pending', 'confirmed')
          AND (
              appointment_time < ?
              OR appointment_time >= ?
          )
    ");

    $bookedStmt->execute([
        $doctorId,
        $date,
        $startTime,
        $endTime
    ]);

    if ((int)$bookedStmt->fetchColumn() > 0) {
        $conn->rollBack();

        sendJson(409, [
            "success" => false,
            "message" => "Existing appointments fall outside these hours"
        ]);
    }

    // Replace this doctor's availability for the selected date.
    $deleteStmt = $conn->prepare("
        DELETE FROM DoctorAvailability
        WHERE doctor_id = ?
          AND available_date = ?
    ");

    $deleteStmt->execute([
        $doctorId,
        $date
    ]);

    $insertStmt = $conn->prepare("
        INSERT INTO DoctorAvailability (
            doctor_id,
            available_date,
            start_time,
            end_time,
            slot_duration,
            is_available
        )
        VALUES (?, ?, ?, ?, ?, 1)
    ");

    $insertStmt->execute([
        $doctorId,
        $date,
        $startTime,
        $endTime,
        $slotDuration
    ]);

    $conn->commit();

    sendJson(200, [
        "success" => true,
        "message" => "Working hours saved successfully",
        "doctor_id" => $doctorId,
        "date" => $date,
        "start_time" => $startTime,
        "end_time" => $endTime
    ]);

} catch (Throwable $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log(
        "Save doctor availability error: " . $e->getMessage()
    );

    sendJson(500, [
        "success" => false,
        "message" => "Unable to save working hours"
    ]);
}
