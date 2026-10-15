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

// Only authenticated administrators can access statistics
if (empty($_SESSION["admin_id"])) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Unauthorized access"
    ]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Method not allowed"
    ]);
    exit;
}

require_once __DIR__ . "/../db.php";

try {
    // Verify that the administrator still exists
    $stmt = $conn->prepare(
        "SELECT id FROM Admins WHERE id = ? LIMIT 1"
    );
    $stmt->execute([$_SESSION["admin_id"]]);

    if (!$stmt->fetchColumn()) {
        http_response_code(401);

        echo json_encode([
            "success" => false,
            "message" => "Administrator account not found"
        ]);
        exit;
    }

    // Read-only statistics from MySQL
    
$stats = [
    "patients" => (int) $conn->query(
        "SELECT COUNT(*) FROM Patient"
    )->fetchColumn(),

    "doctors" => (int) $conn->query(
        "SELECT COUNT(*) FROM Doctor"
    )->fetchColumn(),

    "pharmacists" => (int) $conn->query(
        "SELECT COUNT(*) FROM Pharmacist"
    )->fetchColumn(),

    "pharmacies" => (int) $conn->query(
        "SELECT COUNT(*) FROM Pharmacies"
    )->fetchColumn(),

    "pending_doctors" => (int) $conn->query(
        "SELECT COUNT(*) FROM Doctor WHERE verification = 'pending'"
    )->fetchColumn(),

    "pending_pharmacists" => (int) $conn->query(
        "SELECT COUNT(*) FROM Pharmacist WHERE verification = 'pending'"
    )->fetchColumn()
];

$stats["pending_total"] =
    $stats["pending_doctors"] +
    $stats["pending_pharmacists"];


    echo json_encode([
        "success" => true,
        "stats" => $stats
    ]);

} catch (PDOException $e) {
    error_log("Dashboard stats error: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to load dashboard statistics"
    ]);
}
