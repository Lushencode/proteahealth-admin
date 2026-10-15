<?php

header("Content-Type: application/json");

require_once "db.php";

$doctor_id = isset($_GET["doctor_id"])
    ? intval($_GET["doctor_id"])
    : 0;

$date = isset($_GET["date"])
    ? trim($_GET["date"])
    : "";


if ($doctor_id <= 0 || empty($date)) {

    echo json_encode([
        "success" => false,
        "message" => "Doctor ID and date are required",
        "slots" => []
    ]);

    exit;
}


// -------------------------------------
// Get doctor's availability for the day
// -------------------------------------

$stmt = $conn->prepare("
    SELECT
        start_time,
        end_time,
        slot_duration
    FROM DoctorAvailability
    WHERE doctor_id = ?
      AND available_date = ?
      AND is_available = 1
    ORDER BY start_time
");

$stmt->execute([
    $doctor_id,
    $date
]);

$availabilityRows =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


if (empty($availabilityRows)) {

    echo json_encode([
        "success" => true,
        "message" => "Doctor has no availability on this date",
        "slots" => []
    ]);

    exit;
}


// -------------------------------------
// Get already occupied appointment times
// -------------------------------------

$stmt = $conn->prepare("
    SELECT appointment_time
    FROM Appointments
    WHERE doctor_id = ?
      AND appointment_date = ?
      AND status IN ('pending', 'confirmed')
");

$stmt->execute([
    $doctor_id,
    $date
]);

$bookedRows =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


$bookedTimes = [];

foreach ($bookedRows as $row) {

    $bookedTimes[] =
        date(
            "H:i",
            strtotime($row["appointment_time"])
        );
}


// -------------------------------------
// Generate appointment slots
// -------------------------------------

$slots = [];

foreach ($availabilityRows as $availability) {

    $start =
        strtotime(
            $date . " " .
            $availability["start_time"]
        );

    $end =
        strtotime(
            $date . " " .
            $availability["end_time"]
        );

    $duration =
        intval(
            $availability["slot_duration"]
        );


    if ($duration <= 0) {
        continue;
    }


    while (
        $start + ($duration * 60) <= $end
    ) {

        $slot =
            date("H:i", $start);


        // Only add slot if it is not already booked
        if (!in_array($slot, $bookedTimes)) {

            $slots[] = $slot;
        }


        $start +=
            ($duration * 60);
    }
}


// Remove duplicates in case availability ranges overlap
$slots = array_values(
    array_unique($slots)
);

sort($slots);


// -------------------------------------
// Return result
// -------------------------------------

echo json_encode([
    "success" => true,
    "message" => "Availability loaded successfully",
    "doctor_id" => $doctor_id,
    "date" => $date,
    "slots" => $slots
]);

?>