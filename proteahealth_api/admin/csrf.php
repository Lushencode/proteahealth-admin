
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
        "message" => "Unauthorized"
    ]);
    exit;
}

require_once __DIR__ . "/../db.php";

$stmt = $conn->prepare(
    "SELECT id FROM Admins WHERE id = ? LIMIT 1"
);
$stmt->execute([$_SESSION["admin_id"]]);

if (!$stmt->fetchColumn()) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

if (empty($_SESSION["admin_csrf_token"])) {
    $_SESSION["admin_csrf_token"] =
        bin2hex(random_bytes(32));
}

echo json_encode([
    "success" => true,
    "csrf_token" => $_SESSION["admin_csrf_token"]
]);
