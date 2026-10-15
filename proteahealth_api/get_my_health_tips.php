
<?php

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");

require_once __DIR__ . "/db.php";

function sendResponse($status, $data) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Only allow GET requests.
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    sendResponse(405, [
        "success" => false,
        "message" => "Only GET requests are allowed."
    ]);
}

// Retrieve the Bearer token.
$authorization = $_SERVER["HTTP_AUTHORIZATION"]
    ?? $_SERVER["REDIRECT_HTTP_AUTHORIZATION"]
    ?? "";

if ($authorization === "" && function_exists("getallheaders")) {
    foreach (getallheaders() as $name => $value) {
        if (strcasecmp($name, "Authorization") === 0) {
            $authorization = $value;
            break;
        }
    }
}

if (!preg_match(
    '/^Bearer\s+([a-f0-9]{64})$/i',
    trim($authorization),
    $matches
)) {
    sendResponse(401, [
        "success" => false,
        "message" => "Authentication token missing or invalid."
    ]);
}

$tokenHash = hash("sha256", strtolower($matches[1]));

try {

    // 1. Authenticate the logged-in user.
    $stmt = $conn->prepare("
        SELECT user_id, role
        FROM AuthTokens
        WHERE token_hash = ?
          AND expires_at > UTC_TIMESTAMP()
        LIMIT 1
    ");

    $stmt->execute([$tokenHash]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        sendResponse(401, [
            "success" => false,
            "message" => "Invalid or expired session."
        ]);
    }

    if (strtolower($session["role"]) !== "doctor") {
        sendResponse(403, [
            "success" => false,
            "message" => "Doctor access required."
        ]);
    }

    $doctorId = (int)$session["user_id"];

    // 2. Check doctor verification.
    $stmt = $conn->prepare("
        SELECT verification
        FROM Doctor
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$doctorId]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (
        !$doctor ||
        strtolower($doctor["verification"] ?? "") !== "verified"
    ) {
        sendResponse(403, [
            "success" => false,
            "message" => "Verified doctor access required."
        ]);
    }

    // 3. Retrieve articles belonging to this doctor.
    $stmt = $conn->prepare("
        SELECT
            resource_id,
            category_id,
            title,
            content,
            health_tip,
            image,
            status,
            is_reviewed,
            created_at,
            updated_at
        FROM educational_resource
        WHERE provider_id = ?
        ORDER BY updated_at DESC, resource_id DESC
    ");

    $stmt->execute([$doctorId]);

    $tips = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $tips[] = [
            "resource_id" => (int)$row["resource_id"],
            "category_id" => (int)$row["category_id"],
            "title" => $row["title"],
            "content" => $row["content"],
            "health_tip" => $row["health_tip"],
            "image" => $row["image"],
            "status" => $row["status"],
            "is_reviewed" => (int)$row["is_reviewed"],
            "created_at" => $row["created_at"],
            "updated_at" => $row["updated_at"]
        ];
    }

    sendResponse(200, [
        "success" => true,
        "message" => "Health Tips retrieved successfully.",
        "tips" => $tips
    ]);

} catch (Throwable $e) {

    error_log(
        "Get my health tips error: " . $e->getMessage()
    );

    sendResponse(500, [
        "success" => false,
        "message" => "Unable to retrieve Health Tips."
    ]);
}

?>
