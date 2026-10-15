
<?php

header("Content-Type: application/json");

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

header("Cache-Control: no-store");

if (empty($_SESSION["admin_id"])) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Not authenticated"
    ]);
    exit;
}

require_once __DIR__ . "/../db.php";

try {
    $stmt = $conn->prepare("
        SELECT id, name, surname, email
        FROM Admins
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$_SESSION["admin_id"]]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        $_SESSION = [];
        session_destroy();

        http_response_code(401);

        echo json_encode([
            "success" => false,
            "message" => "Administrator account not found"
        ]);
        exit;
    }

    echo json_encode([
        "success" => true,
        "admin" => $admin
    ]);

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to verify session"
    ]);
}
