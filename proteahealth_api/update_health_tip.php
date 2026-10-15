
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

// Retrieve Bearer token.
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

// Accept JSON or form data.
$json = json_decode(file_get_contents("php://input"), true);
$input = is_array($json) ? $json : $_POST;

$resourceId = filter_var(
    $input["resource_id"] ?? null,
    FILTER_VALIDATE_INT
);

$categoryId = filter_var(
    $input["category_id"] ?? null,
    FILTER_VALIDATE_INT
);

$title = trim((string)($input["title"] ?? ""));
$content = trim((string)($input["content"] ?? ""));
$healthTip = trim((string)($input["health_tip"] ?? ""));
$status = strtolower(trim((string)($input["status"] ?? "")));

if (!$resourceId || $resourceId <= 0) {
    sendResponse(400, [
        "success" => false,
        "message" => "Invalid Health Tip ID."
    ]);
}

if (!$categoryId || $categoryId <= 0) {
    sendResponse(400, [
        "success" => false,
        "message" => "Please select a valid category."
    ]);
}

$titleLength = function_exists("mb_strlen")
    ? mb_strlen($title, "UTF-8")
    : strlen($title);

if ($title === "" || $titleLength > 255) {
    sendResponse(400, [
        "success" => false,
        "message" => "A title of up to 255 characters is required."
    ]);
}

if (!in_array($status, ["draft", "published"], true)) {
    sendResponse(400, [
        "success" => false,
        "message" => "Invalid publication status."
    ]);
}

if (
    $status === "published" &&
    ($content === "" || $healthTip === "")
) {
    sendResponse(400, [
        "success" => false,
        "message" => "Published articles require content and a health tip."
    ]);
}

try {

    // 1. Authenticate the session.
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
            "message" => "Verified doctor access required."
        ]);
    }

    // 3. Check that the article belongs to this doctor.
    $stmt = $conn->prepare("
        SELECT resource_id
        FROM educational_resource
        WHERE resource_id = ?
          AND provider_id = ?
        LIMIT 1
    ");

    $stmt->execute([$resourceId, $doctorId]);

    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        sendResponse(404, [
            "success" => false,
            "message" => "Health Tip not found or access denied."
        ]);
    }

    // 4. Validate the category.
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

    // 5. Recalculate summary and reading time.
    $summarySource = $content !== "" ? $content : $healthTip;

    $summary = function_exists("mb_substr")
        ? mb_substr($summarySource, 0, 250, "UTF-8")
        : substr($summarySource, 0, 250);

    $wordCount = str_word_count($content);
    $readTime = max(1, (int)ceil($wordCount / 200));

    // Matches the existing verified-doctor publishing workflow.
    $isReviewed = $status === "published" ? 1 : 0;

    // 6. Update only this doctor's article.
    $stmt = $conn->prepare("
        UPDATE educational_resource
        SET
            category_id = ?,
            title = ?,
            summary = ?,
            content = ?,
            health_tip = ?,
            read_time = ?,
            status = ?,
            is_reviewed = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE resource_id = ?
          AND provider_id = ?
    ");

    $stmt->execute([
        $categoryId,
        $title,
        $summary,
        $content,
        $healthTip,
        $readTime,
        $status,
        $isReviewed,
        $resourceId,
        $doctorId
    ]);

    sendResponse(200, [
        "success" => true,
        "message" => "Health Tip updated successfully.",
        "resource_id" => $resourceId,
        "status" => $status
    ]);

} catch (Throwable $e) {

    error_log("Update health tip error: " . $e->getMessage());

    sendResponse(500, [
        "success" => false,
        "message" => "Unable to update Health Tip."
    ]);
}

?>
