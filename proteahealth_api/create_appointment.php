<?php

header("Content-Type: application/json");

require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed"
    ]);
    exit;
}

$patient_id = isset($_POST["patient_id"])
    ? intval($_POST["patient_id"])
    : 0;

$doctor_id = isset($_POST["doctor_id"])
    ? intval($_POST["doctor_id"])
    : 0;

$appointment_type =
    trim($_POST["appointment_type"] ?? "");

$appointment_date =
    trim($_POST["appointment_date"] ?? "");

$appointment_time =
    trim($_POST["appointment_time"] ?? "");

$reason =
    trim($_POST["reason"] ?? "");


if (
    $patient_id <= 0 ||
    $doctor_id <= 0 ||
    empty($appointment_type) ||
    empty($appointment_date) ||
    empty($appointment_time) ||
    empty($reason)
) {

    echo json_encode([
        "success" => false,
        "message" => "All appointment fields are required"
    ]);

    exit;
}


try {

    // -----------------------------------
    // Check that doctor exists
    // -----------------------------------

    $stmt = $conn->prepare("
        SELECT id
        FROM Doctor
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$doctor_id]);

    if (!$stmt->fetch()) {

        echo json_encode([
            "success" => false,
            "message" => "Doctor not found"
        ]);

        exit;
    }


    // -----------------------------------
    // Check that patient exists
    // -----------------------------------

    $stmt = $conn->prepare("
        SELECT id
        FROM Patient
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$patient_id]);

    if (!$stmt->fetch()) {

        echo json_encode([
            "success" => false,
            "message" => "Patient not found"
        ]);

        exit;
    }


    // -----------------------------------
    // Check doctor availability
    // -----------------------------------

    $stmt = $conn->prepare("
        SELECT id
        FROM DoctorAvailability
        WHERE doctor_id = ?
          AND available_date = ?
          AND is_available = 1
          AND ? >= start_time
          AND ? < end_time
        LIMIT 1
    ");

    $stmt->execute([
        $doctor_id,
        $appointment_date,
        $appointment_time,
        $appointment_time
    ]);

    if (!$stmt->fetch()) {

        echo json_encode([
            "success" => false,
            "message" => "Doctor is not available at this time"
        ]);

        exit;
    }


    // -----------------------------------
    // Check doctor isn't already booked
    // -----------------------------------

    $stmt = $conn->prepare("
        SELECT id
        FROM Appointments
        WHERE doctor_id = ?
          AND appointment_date = ?
          AND appointment_time = ?
          AND status IN ('pending', 'confirmed')
        LIMIT 1
    ");

    $stmt->execute([
        $doctor_id,
        $appointment_date,
        $appointment_time
    ]);

    if ($stmt->fetch()) {

        echo json_encode([
            "success" => false,
            "message" => "This appointment time is no longer available"
        ]);

        exit;
    }


    // -----------------------------------
    // Check patient isn't double booked
    // -----------------------------------

    $stmt = $conn->prepare("
        SELECT id
        FROM Appointments
        WHERE patient_id = ?
          AND appointment_date = ?
          AND appointment_time = ?
          AND status IN ('pending', 'confirmed')
        LIMIT 1
    ");

    $stmt->execute([
        $patient_id,
        $appointment_date,
        $appointment_time
    ]);

    if ($stmt->fetch()) {

        echo json_encode([
            "success" => false,
            "message" => "You already have an appointment at this time"
        ]);

        exit;
    }


    // -----------------------------------
    // Create pending appointment
    // -----------------------------------

    
    // -----------------------------------
    // Create appointment and notify doctor
    // -----------------------------------

    $conn->beginTransaction();

    // 1. Create pending appointment
    $stmt = $conn->prepare("
        INSERT INTO Appointments
        (
            patient_id,
            doctor_id,
            appointment_type,
            appointment_date,
            appointment_time,
            reason,
            accepted,
            status
        )
        VALUES (?, ?, ?, ?, ?, ?, NULL, 'pending')
    ");

    $stmt->execute([
        $patient_id,
        $doctor_id,
        $appointment_type,
        $appointment_date,
        $appointment_time,
        $reason
    ]);

    // Get the ID of the new appointment
    $appointment_id = (int) $conn->lastInsertId();






    // -----------------------------------
    // 2. Create notification for doctor
    // -----------------------------------

    $formattedDate = date(
        "d M Y",
        strtotime($appointment_date)
    );

    $formattedTime = date(
        "H:i",
        strtotime($appointment_time)
    );

    $notificationMessage =
        "New appointment request for " .
        $formattedDate .
        " at " .
        $formattedTime .
        ". Please review the appointment.";


    $stmt = $conn->prepare("
        INSERT INTO notifications
        (
            doctor_id,
            message,
            is_read
        )
        VALUES (?, ?, 0)
    ");

    $stmt->execute([
        $doctor_id,
        $notificationMessage
    ]);


    // -----------------------------------
    // 3. Save both database changes
    // -----------------------------------

    $conn->commit();


    echo json_encode([
        "success" => true,
        "message" => "Appointment request sent successfully",
        "appointment_id" => $appointment_id
    ]);


} catch (Throwable $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log(
        "Create appointment error: " . $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to create appointment"
    ]);
}

?>
