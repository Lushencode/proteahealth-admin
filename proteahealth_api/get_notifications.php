
<?php

header('Content-Type: application/json');

require_once __DIR__ . "/db.php";

// Identify the logged-in user's role
$role = strtolower(trim($_GET['role'] ?? 'patient'));

// Determine which database column to use
switch ($role) {

    case 'doctor':
        $idColumn = 'doctor_id';
        $userId = intval($_GET['doctor_id'] ?? 0);
        break;

    case 'pharmacist':
    case 'pharmacy':
        $idColumn = 'pharmacist_id';
        $userId = intval($_GET['pharmacist_id'] ?? 0);
        break;

    case 'patient':
        $idColumn = 'patient_id';
        $userId = intval($_GET['patient_id'] ?? 0);
        break;

    default:
        echo json_encode([
            "success" => false,
            "message" => "Invalid user role"
        ]);
        exit;
}

// Validate the correct role-specific ID
if ($userId <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid user ID for role: " . $role
    ]);
    exit;
}

try {

    // Column name is safe because it comes from
    // the fixed whitelist in the switch statement.
    
$sql = "
    SELECT
        notification_id,
        patient_id,
        doctor_id,
        pharmacist_id,
        message,
        sent_at,
        is_read
    FROM notifications
    WHERE {$idColumn} = :user_id
    ORDER BY sent_at DESC, notification_id DESC
";




    $stmt = $conn->prepare($sql);

    $stmt->bindValue(
        ':user_id',
        $userId,
        PDO::PARAM_INT
    );

    $stmt->execute();

    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($notifications as &$notification) {

        $notification['notification_id'] =
            (int) $notification['notification_id'];

        $notification['patient_id'] =
            $notification['patient_id'] !== null
                ? (int) $notification['patient_id']
                : null;

        $notification['doctor_id'] =
            $notification['doctor_id'] !== null
                ? (int) $notification['doctor_id']
                : null;

        $notification['pharmacist_id'] =
            $notification['pharmacist_id'] !== null
                ? (int) $notification['pharmacist_id']
                : null;

        $notification['is_read'] =
            (bool) $notification['is_read'];
    }

    unset($notification);

    echo json_encode([
        "success" => true,
        "notifications" => $notifications
    ]);

} catch (PDOException $e) {

    error_log("Notification query error: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to retrieve notifications"
    ]);
}

?>
