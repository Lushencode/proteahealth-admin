
<?php

// Only allow execution from the server terminal/scheduler.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Forbidden");
}

date_default_timezone_set("Africa/Johannesburg");

require_once __DIR__ . "/db.php";

try {

    $conn->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    // Keep MySQL's current time aligned with SA time.
    $conn->exec("SET time_zone = '+02:00'");

    // Find confirmed appointments happening within 24 hours.
    $stmt = $conn->prepare("
        SELECT
            a.id,
            a.patient_id,
            a.doctor_id,
            a.appointment_date,
            a.appointment_time
        FROM Appointments a
        WHERE a.status = 'confirmed'
          AND TIMESTAMP(
                a.appointment_date,
                a.appointment_time
              ) > NOW()
          AND TIMESTAMP(
                a.appointment_date,
                a.appointment_time
              ) <= DATE_ADD(NOW(), INTERVAL 1 DAY)
          AND NOT EXISTS (
              SELECT 1
              FROM appointment_reminder_log r
              WHERE r.appointment_id = a.id
          )
    ");

    $stmt->execute();

    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sentCount = 0;

    foreach ($appointments as $appointment) {

        $conn->beginTransaction();

        try {

            // Claim this reminder exactly once.
            $log = $conn->prepare("
                INSERT IGNORE INTO appointment_reminder_log
                    (appointment_id)
                VALUES (?)
            ");

            $log->execute([
                $appointment["id"]
            ]);

            // Another scheduler run already claimed it.
            if ($log->rowCount() === 0) {
                $conn->rollBack();
                continue;
            }

            $date = date(
                "d M Y",
                strtotime($appointment["appointment_date"])
            );

            $time = date(
                "H:i",
                strtotime($appointment["appointment_time"])
            );

            $message =
                "Appointment reminder: You have a confirmed " .
                "appointment on $date at $time.";

            // Send reminder to patient.
            $notification = $conn->prepare("
                INSERT INTO notifications (
                    patient_id,
                    message,
                    is_read
                )
                VALUES (?, ?, 0)
            ");

            $notification->execute([
                $appointment["patient_id"],
                $message
            ]);

            // Save the reminder and notification together.
            $conn->commit();

            $sentCount++;

        } catch (Throwable $e) {

            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            error_log(
                "Appointment reminder failed for ID " .
                $appointment["id"] . ": " .
                $e->getMessage()
            );
        }
    }

    echo "Appointment reminders created: $sentCount\n";

} catch (Throwable $e) {

    error_log(
        "Reminder scheduler error: " . $e->getMessage()
    );

    exit(1);
}
?>
