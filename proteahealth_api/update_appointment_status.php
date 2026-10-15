
<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/db.php";

function respond($code, $data) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// -----------------------------------
// 1. Validate request method
// -----------------------------------

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    respond(405, [
        "success" => false,
        "message" => "POST request required"
    ]);
}

// -----------------------------------
// 2. Validate appointment and action
// -----------------------------------

$appointmentId = filter_var(
    $_POST["appointment_id"] ?? null,
    FILTER_VALIDATE_INT
);

$action = strtolower(
    trim($_POST["action"] ?? "")
);

if (!$appointmentId || $appointmentId <= 0) {
    respond(400, [
        "success" => false,
        "message" => "Invalid appointment ID"
    ]);
}

if (!in_array($action, ["confirm", "decline"], true)) {
    respond(400, [
        "success" => false,
        "message" => "Invalid appointment action"
    ]);
}

// -----------------------------------
// 3. Authenticate logged-in doctor
// -----------------------------------

$authHeader = $_SERVER["HTTP_AUTHORIZATION"] ?? "";

if (!$authHeader && function_exists("getallheaders")) {
    foreach (getallheaders() as $key => $value) {
        if (strcasecmp($key, "Authorization") === 0) {
            $authHeader = $value;
            break;
        }
    }
}

if (!preg_match(
    '/^Bearer ([a-f0-9]{64})$/i',
    trim($authHeader),
    $matches
)) {
    respond(401, [
        "success" => false,
        "message" => "Authentication token required"
    ]);
}

$tokenHash = hash("sha256", $matches[1]);

$newStatus = $action === "confirm"
    ? "confirmed"
    : "rejected";

$accepted = $action === "confirm" ? 1 : 0;

$doctorResponse = $action === "confirm"
    ? "Appointment confirmed by doctor"
    : "Appointment declined by doctor";

try {

    // -----------------------------------
    // 4. Identify authenticated doctor
    // -----------------------------------

    $authStmt = $conn->prepare("
        SELECT user_id
        FROM AuthTokens
        WHERE token_hash = ?
          AND role = 'doctor'
          AND expires_at > UTC_TIMESTAMP()
        LIMIT 1
    ");

    $authStmt->execute([$tokenHash]);

    $doctorId = $authStmt->fetchColumn();

    if ($doctorId === false) {
        respond(401, [
            "success" => false,
            "message" => "Invalid or expired doctor token"
        ]);
    }

    $doctorId = (int)$doctorId;

    // -----------------------------------
    // 5. Begin database transaction
    // -----------------------------------

    $conn->beginTransaction();

    // Lock appointment during processing
    $stmt = $conn->prepare("
        SELECT
            id,
            patient_id,
            doctor_id,
            appointment_date,
            appointment_time,
            status
        FROM Appointments
        WHERE id = ?
        FOR UPDATE
    ");

    $stmt->execute([$appointmentId]);

    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appointment) {
        $conn->rollBack();

        respond(404, [
            "success" => false,
            "message" => "Appointment not found"
        ]);
    }

    // Verify appointment belongs to doctor
    if ((int)$appointment["doctor_id"] !== $doctorId) {
        $conn->rollBack();

        respond(403, [
            "success" => false,
            "message" => "Appointment does not belong to this doctor"
        ]);
    }

    // Only pending appointments can be processed
    if (strtolower($appointment["status"]) !== "pending") {
        $conn->rollBack();

        respond(409, [
            "success" => false,
            "message" => "Only pending appointments can be updated"
        ]);
    }

    // -----------------------------------
    // 6. Update appointment status
    // -----------------------------------

    $update = $conn->prepare("
        UPDATE Appointments
        SET
            status = ?,
            accepted = ?,
            doctor_response = ?
        WHERE id = ?
          AND doctor_id = ?
          AND status = 'pending'
    ");

    $update->execute([
        $newStatus,
        $accepted,
        $doctorResponse,
        $appointmentId,
        $doctorId
    ]);

    if ($update->rowCount() !== 1) {
        throw new RuntimeException(
            "Appointment update was not applied"
        );
    }

    // -----------------------------------
    // 7. Assign doctor ONLY if confirmed
    // -----------------------------------

    if ($action === "confirm") {

        $assignStmt = $conn->prepare("
            INSERT INTO DoctorPatient
            (
                doctor_id,
                patient_id
            )
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE
                doctor_id = doctor_id
        ");

        $assignStmt->execute([
            $doctorId,
            (int)$appointment["patient_id"]
        ]);
    }

    // -----------------------------------
    // 8. Notify patient
    // -----------------------------------

    $formattedDate = date(
        "d M Y",
        strtotime($appointment["appointment_date"])
    );

    $formattedTime = date(
        "H:i",
        strtotime($appointment["appointment_time"])
    );

    $message = $action === "confirm"
        ? "Your appointment on $formattedDate at $formattedTime has been confirmed."
        : "Your appointment request for $formattedDate at $formattedTime has been declined.";

    $notification = $conn->prepare("
        INSERT INTO notifications
        (
            patient_id,
            message,
            is_read
        )
        VALUES (?, ?, 0)
    ");

    $notification->execute([
        (int)$appointment["patient_id"],
        $message
    ]);

    // -----------------------------------
    // 9. Commit all database changes
    // -----------------------------------

    $conn->commit();

    respond(200, [
        "success" => true,
        "message" => $action === "confirm"
            ? "Appointment confirmed successfully"
            : "Appointment declined successfully",
        "appointment_id" => $appointmentId,
        "status" => $newStatus
    ]);

} catch (Throwable $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log(
        "Appointment update error: " . $e->getMessage()
    );

    respond(500, [
        "success" => false,
        "message" => "Unable to update appointment"
    ]);
}

?>
