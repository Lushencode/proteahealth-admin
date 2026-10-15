
<?php

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");

require_once __DIR__ . "/db.php";

function sendResponse($status, $data) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    sendResponse(405, [
        "success" => false,
        "message" => "Only POST requests are allowed."
    ]);
}

// Read the Bearer token.
$authorization = $_SERVER["HTTP_AUTHORIZATION"]
    ?? $_SERVER["REDIRECT_HTTP_AUTHORIZATION"]
    ?? "";

if ($authorization === "" && function_exists("getallheaders")) {
    foreach (getallheaders() as $key => $value) {
        if (strcasecmp($key, "Authorization") === 0) {
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

// Accept JSON or form-encoded requests.
$json = json_decode(file_get_contents("php://input"), true);
$input = is_array($json) ? $json : [];

$categoryName = trim(
    $input["name"]
    ?? $_POST["name"]
    ?? ""
);

if ($categoryName === "" || strlen($categoryName) > 150) {
    sendResponse(400, [
        "success" => false,
        "message" => "Category name must be between 1 and 150 bytes."
    ]);
}

if (preg_match('/[\x00-\x1F\x7F]/', $categoryName)) {
    sendResponse(400, [
        "success" => false,
        "message" => "Category name contains invalid characters."
    ]);
}

$lockAcquired = false;

try {

    // Verify the login token.
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
            "message" => "Session expired or invalid."
        ]);
    }

    if (strtolower($session["role"]) !== "doctor") {
        sendResponse(403, [
            "success" => false,
            "message" => "Only doctors can create categories."
        ]);
    }

    // Verify the doctor.
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
        strtolower($doctor["verification"]) !== "verified"
    ) {
        sendResponse(403, [
            "success" => false,
            "message" => "Only verified doctors can create categories."
        ]);
    }

    // Serialize category creation to avoid duplicate global names.
    $lockStmt = $conn->query("
        SELECT GET_LOCK('proteahealth_category_create', 5)
    ");

    if ((int)$lockStmt->fetchColumn() !== 1) {
        sendResponse(503, [
            "success" => false,
            "message" => "Category service is busy. Please retry."
        ]);
    }

    $lockAcquired = true;

    // Reuse an existing global category if the name matches.
    $stmt = $conn->prepare("
        SELECT category_id, name
        FROM Education_Category
        WHERE patient_id IS NULL
          AND name = ?
        LIMIT 1
    ");

    $stmt->execute([$categoryName]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $result = [
            "success" => true,
            "message" => "Category already exists.",
            "category_id" => (int)$existing["category_id"],
            "name" => $existing["name"],
            "created" => false
        ];

    } else {

        // New categories are global, not patient-specific.
        $stmt = $conn->prepare("
            INSERT INTO Education_Category (name, patient_id)
            VALUES (?, NULL)
        ");

        $stmt->execute([$categoryName]);

        $result = [
            "success" => true,
            "message" => "Category created successfully.",
            "category_id" => (int)$conn->lastInsertId(),
            "name" => $categoryName,
            "created" => true
        ];
    }

    $conn->query("
        SELECT RELEASE_LOCK('proteahealth_category_create')
    ");
    $lockAcquired = false;

    sendResponse(200, $result);

} catch (Throwable $e) {

    error_log("Create category error: " . $e->getMessage());

    sendResponse(500, [
        "success" => false,
        "message" => "Unable to create category."
    ]);

} finally {

    if ($lockAcquired) {
        try {
            $conn->query("
                SELECT RELEASE_LOCK('proteahealth_category_create')
            ");
        } catch (Throwable $ignored) {
        }
    }
}
?>

