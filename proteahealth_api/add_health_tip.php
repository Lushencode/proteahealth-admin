
<?php

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");

require_once __DIR__ . "/db.php";

function sendResponse($status, $data) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Allow POST only.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    sendResponse(405, [
        "success" => false,
        "message" => "Only POST requests are allowed."
    ]);
}

// Read Bearer token.
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

// Accept JSON or form-encoded requests.
$json = json_decode(file_get_contents("php://input"), true);
$input = is_array($json) ? $json : [];

$categoryId = intval(
    $input["category_id"]
    ?? $_POST["category_id"]
    ?? 0
);

$title = trim(
    $input["title"]
    ?? $_POST["title"]
    ?? ""
);

$content = trim(
    $input["content"]
    ?? $_POST["content"]
    ?? ""
);

$healthTip = trim(
    $input["health_tip"]
    ?? $_POST["health_tip"]
    ?? ""
);

$status = strtolower(trim(
    $input["status"]
    ?? $_POST["status"]
    ?? "draft"
));

// Validate input.
if ($categoryId <= 0) {
    sendResponse(400, [
        "success" => false,
        "message" => "Please select a valid category."
    ]);
}

if ($title === "" || strlen($title) > 255) {
    sendResponse(400, [
        "success" => false,
        "message" => "A title of up to 255 bytes is required."
    ]);
}

if (!in_array($status, ["draft", "published"], true)) {
    sendResponse(400, [
        "success" => false,
        "message" => "Invalid publication status."
    ]);
}

if ($status === "published" &&
    ($content === "" || $healthTip === "")) {

    sendResponse(400, [
        "success" => false,
        "message" => "Published articles require content and a health tip."
    ]);
}

try {

    // 1. Authenticate the doctor.
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
            "message" => "Only doctors can create health tips."
        ]);
    }

    $doctorId = intval($session["user_id"]);

    // 2. Verify doctor approval.
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
            "message" => "Only verified doctors can create health tips."
        ]);
    }

    // 3. Confirm the category is available to the Health Hub.
    $stmt = $conn->prepare("
        SELECT ec.category_id
        FROM Education_Category ec
        WHERE ec.category_id = ?
          AND (
              ec.patient_id IS NULL
              OR EXISTS (
                  SELECT 1
                  FROM educational_resource er
                  WHERE er.category_id = ec.category_id
              )
          )
        LIMIT 1
    ");

    $stmt->execute([$categoryId]);

    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        sendResponse(400, [
            "success" => false,
            "message" => "Selected category does not exist."
        ]);
    }

    // 4. Generate a summary and reading time.
    $summarySource = $content !== "" ? $content : $healthTip;

    $summary = function_exists("mb_substr")
        ? mb_substr($summarySource, 0, 250, "UTF-8")
        : substr($summarySource, 0, 250);

    $wordCount = str_word_count($content);
    $readTime = max(1, (int)ceil($wordCount / 200));

    // Verified doctors can publish directly under this workflow.
    $isReviewed = $status === "published" ? 1 : 0;

    // 5. Insert the health tip.
    $stmt = $conn->prepare("
        INSERT INTO educational_resource (
            category_id,
            provider_id,
            title,
            image,
            summary,
            content,
            health_tip,
            read_time,
            status,
            is_reviewed
        )
        VALUES (?, ?, ?, NULL, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $categoryId,
        $doctorId,
        $title,
        $summary,
        $content,
        $healthTip,
        $readTime,
        $status,
        $isReviewed
    ]);

    $resourceId = (int)$conn->lastInsertId();

    sendResponse(201, [
        "success" => true,
        "message" => $status === "published"
            ? "Health tip published successfully."
            : "Draft saved successfully.",
        "resource_id" => $resourceId,
        "status" => $status
    ]);

} catch (Throwable $e) {

    error_log("Add health tip error: " . $e->getMessage());

    sendResponse(500, [
        "success" => false,
        "message" => "Unable to save health tip."
    ]);
}

?>
