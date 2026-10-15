
<?php

header("Content-Type: application/json");
header("Cache-Control: no-store");

session_name("proteahealth_admin");

session_set_cookie_params([
    "lifetime" => 0,
    "path" => "/",
    "secure" => !empty($_SERVER["HTTPS"]) &&
                $_SERVER["HTTPS"] !== "off",
    "httponly" => true,
    "samesite" => "Lax"
]);

session_start();

// Only allow GET requests
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed"
    ]);
    exit;
}

// Require administrator authentication
if (empty($_SESSION["admin_id"])) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized access"
    ]);
    exit;
}

require_once __DIR__ . "/../db.php";

try {
    // Verify the administrator still exists
    $adminStmt = $conn->prepare("
        SELECT id
        FROM Admins
        WHERE id = ?
        LIMIT 1
    ");

    $adminStmt->execute([
        $_SESSION["admin_id"]
    ]);

    if (!$adminStmt->fetchColumn()) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Administrator not found"
        ]);
        exit;
    }

    // Retrieve verification history
    $stmt = $conn->prepare("
        SELECT
            audit.id,
            audit.doctor_id,
            audit.admin_id,
            audit.previous_status,
            audit.new_status,
            audit.decision_reason,
            audit.credential_reference,
            audit.created_at,

            doctor.name AS doctor_name,
            doctor.surname AS doctor_surname,

            admin.name AS admin_name,
            admin.surname AS admin_surname

        FROM DoctorVerificationAudit AS audit

        LEFT JOIN Doctor AS doctor
            ON audit.doctor_id = doctor.id

        LEFT JOIN Admins AS admin
            ON audit.admin_id = admin.id

        ORDER BY audit.id DESC

        LIMIT 100
    ");

    $stmt->execute();

    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "history" => $history
    ]);

} catch (Throwable $e) {
    error_log(
        "Doctor verification history error: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to retrieve verification history"
    ]);
}
