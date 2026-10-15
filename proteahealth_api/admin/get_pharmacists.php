
<?php

header("Content-Type: application/json");
header("Cache-Control: no-store");

session_name("proteahealth_admin");

session_set_cookie_params([
    "lifetime" => 0,
    "path" => "/",
    "secure" => !empty($_SERVER["HTTPS"])
        && $_SERVER["HTTPS"] !== "off",
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

// Check administrator session
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

    // Retrieve pharmacist applications
    // Never return password hashes
    
$stmt = $conn->prepare("
    SELECT
    p.id,
    p.name,
    p.surname,
    p.email,
    p.phone,
    p.pharmacy_id,
    ph.name AS pharmacy_name,
    ph.verification AS pharmacy_verification,
    p.pharmacy_location,
    p.registration_code,
    p.work_experience_years,
    p.verification,
    p.created_at
FROM Pharmacist AS p
LEFT JOIN Pharmacies AS ph
    ON p.pharmacy_id = ph.id
ORDER BY
    CASE p.verification
        WHEN 'pending' THEN 0
        WHEN 'verified' THEN 1
        WHEN 'rejected' THEN 2
        ELSE 3
    END,
    p.created_at DESC,
    p.id DESC
");

$stmt->execute();

$pharmacists = $stmt->fetchAll(PDO::FETCH_ASSOC);


    echo json_encode([
        "success" => true,
        "pharmacists" => $pharmacists
    ]);

} catch (Throwable $e) {

    error_log(
        "Get pharmacists error: " . $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to retrieve pharmacists"
    ]);
}
