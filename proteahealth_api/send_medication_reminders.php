
<?php

// This script must run from a server-side scheduler,
// not through a public URL.
if (PHP_SAPI !== "cli") {
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

    $conn->exec("SET time_zone = '+02:00'");

    // Find medication reminders due in the last
    // 10 minutes. Designed for a 5-minute scheduler.
    $stmt = $conn->prepare("
        SELECT
            p.id AS prescription_id,
            p.patient_id,
            p.dosage,
            p.scheduled_time,
            pm.medication_name
        FROM Prescriptions p
        INNER JOIN Pharmacy_Medication pm
            ON p.medication_id = pm.id
        WHERE p.scheduled_time IS NOT NULL
          AND p.remaining_doses > 0
          AND TIMESTAMP(
                CURDATE(),
                p.scheduled_time
              ) <= NOW()
          AND TIMESTAMP(
                CURDATE(),
                p.scheduled_time
              ) > DATE_SUB(
                NOW(),
                INTERVAL 10 MINUTE
              )
    ");

    $stmt->execute();

    $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sentCount = 0;

    foreach ($prescriptions as $prescription) {

        try {

            $conn->beginTransaction();

            $prescriptionId =
                (int) $prescription["prescription_id"];

            $patientId =
                (int) $prescription["patient_id"];

            $medicationName =
                $prescription["medication_name"];

            $dosage =
                trim($prescription["dosage"] ?? "");

            $scheduledTime =
                $prescription["scheduled_time"];

            $today = date("Y-m-d");

            // Record reminder once per prescription,
            // date and scheduled time.
            $log = $conn->prepare("
                INSERT IGNORE INTO medication_reminder_log
                (
                    prescription_id,
                    reminder_date,
                    scheduled_time
                )
                VALUES (?, ?, ?)
            ");

            $log->execute([
                $prescriptionId,
                $today,
                $scheduledTime
            ]);

            // Skip if this reminder was already created.
            if ($log->rowCount() === 0) {
                $conn->rollBack();
                continue;
            }

            $formattedTime = date(
                "H:i",
                strtotime($scheduledTime)
            );

            $message =
                "Medication reminder: It's time for your " .
                "scheduled medication, " .
                $medicationName . ".";

            if ($dosage !== "") {
                $message .=
                    " Prescribed dosage: " . $dosage . ".";
            }

            $message .=
                " Scheduled time: " . $formattedTime . ".";

            // Create patient notification.
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
                $patientId,
                $message
            ]);

            // Both the log and notification are saved.
            $conn->commit();

            $sentCount++;

            echo "Reminder created for patient " .
                $patientId .
                ", prescription " .
                $prescriptionId . "\n";

        } catch (Throwable $e) {

            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            error_log(
                "Medication reminder error: " .
                $e->getMessage()
            );
        }
    }

    echo "Total medication reminders created: " .
        $sentCount . "\n";

} catch (Throwable $e) {

    error_log(
        "Medication scheduler failed: " .
        $e->getMessage()
    );

    exit(1);
}

?>
