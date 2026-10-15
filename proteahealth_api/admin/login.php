
<?php

header("Content-Type: application/json");

require_once __DIR__ . "/../db.php";

// Configure secure administrator sessions.
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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method"
    ]);
    exit;
}

// Receive JSON from React.
$data = json_decode(file_get_contents("php://input"), true);

$email = strtolower(trim($data["email"] ?? ""));
$password = $data["password"] ?? "";

if ($email === "" || $password === "") {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Email and password are required"
    ]);
    exit;
}

try {

    $stmt = $conn->prepare("
        SELECT id, name, surname, email, password
        FROM Admins
        WHERE email = ?
        LIMIT 1
    ");

    $stmt->execute([$email]);

    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin || !password_verify($password, $admin["password"])) {
        http_response_code(401);

        echo json_encode([
            "success" => false,
            "message" => "Invalid email or password"
        ]);
        exit;
    }

    // Protect against session fixation.
    session_regenerate_id(true);

    $_SESSION["admin_id"] = (int)$admin["id"];
    $_SESSION["admin_email"] = $admin["email"];
    $_SESSION["admin_name"] = $admin["name"];

    echo json_encode([
        "success" => true,
        "message" => "Login successful",
        "admin" => [
            "id" => (int)$admin["id"],
            "name" => $admin["name"],
            "surname" => $admin["surname"],
            "email" => $admin["email"]
        ]
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to complete login"
    ]);
}
