
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

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed"
    ]);
    exit;
}

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
    // Confirm administrator still exists
    $adminStmt = $conn->prepare(
        "SELECT id FROM Admins WHERE id = ? LIMIT 1"
    );

    $adminStmt->execute([$_SESSION["admin_id"]]);

    if (!$adminStmt->fetchColumn()) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Administrator account not found"
        ]);
        exit;
    }

    // Retrieve doctor applications without passwords
    $stmt = $conn->prepare("
        SELECT
            id,
            name,
            surname,
            email,
            specialization,
            location,
            clinic_name,
            years_in_professional,
            consultation_price,
            certification,
            verification,
            created_at
        FROM Doctor
        ORDER BY
            CASE verification
                WHEN 'pending' THEN 0
                WHEN 'verified' THEN 1
                ELSE 2
            END,
            created_at DESC
    ");

    $stmt->execute();

    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "doctors" => $doctors
    ]);

} catch (PDOException $e) {
    error_log("Get doctors error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Unable to retrieve doctor applications"
    ]);
}
