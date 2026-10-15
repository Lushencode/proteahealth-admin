
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

// Read the Bearer token.
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

    // Verify the logged-in user.
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

    // Only doctors can access the publishing categories.
    if (strtolower($session["role"]) !== "doctor") {
        sendResponse(403, [
            "success" => false,
            "message" => "Doctor access required."
        ]);
    }

    // Check doctor verification.
    $stmt = $conn->prepare("
        SELECT verification
        FROM Doctor
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([(int)$session["user_id"]]);
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

    // Retrieve global categories and categories
    // already used by educational resources.
    $stmt = $conn->prepare("
        SELECT ec.category_id, ec.name
        FROM Education_Category ec
        WHERE ec.patient_id IS NULL
           OR EXISTS (
               SELECT 1
               FROM educational_resource er
               WHERE er.category_id = ec.category_id
           )
        ORDER BY ec.name ASC, ec.category_id ASC
    ");

    $stmt->execute();

    $categories = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $categories[] = [
            "category_id" => (int)$row["category_id"],
            "name" => $row["name"]
        ];
    }

    sendResponse(200, [
        "success" => true,
        "message" => "Categories retrieved successfully.",
        "categories" => $categories
    ]);

} catch (Throwable $e) {

    error_log(
        "Get education categories error: " . $e->getMessage()
    );

    sendResponse(500, [
        "success" => false,
        "message" => "Unable to retrieve categories."
    ]);
}

?>
