
<?php

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");

require_once __DIR__ . "/db.php";

function sendResponse($status, $success, $message, $extra = []) {
    http_response_code($status);

    echo json_encode(array_merge([
        "success" => $success,
        "message" => $message
    ], $extra));

    exit;
}

// Only allow POST requests.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    sendResponse(405, false, "Only POST requests are allowed.");
}

// Retrieve authentication token.
$authorization =
    $_SERVER["HTTP_AUTHORIZATION"]
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
    '/^Bearer\s+([a-f0-9]{64})$/',
    trim($authorization),
    $matches
)) {
    sendResponse(401, false, "Authentication token missing or invalid.");
}

$tokenHash = hash("sha256", $matches[1]);

// Validate article ID.
$resourceId = filter_var(
    $_POST["resource_id"] ?? null,
    FILTER_VALIDATE_INT
);

if ($resourceId === false || $resourceId === null || $resourceId <= 0) {
    sendResponse(400, false, "Invalid Health Tip ID.");
}

// Validate uploaded file.
if (!isset($_FILES["image"])) {
    sendResponse(400, false, "No image was uploaded.");
}

$file = $_FILES["image"];

if (
    !is_array($file)
    || is_array($file["error"] ?? null)
    || ($file["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
) {
    sendResponse(400, false, "Image upload failed.");
}

// Maximum image size: 5 MB.
$maxSize = 5 * 1024 * 1024;

if (
    !isset($file["size"])
    || $file["size"] <= 0
    || $file["size"] > $maxSize
) {
    sendResponse(400, false, "Image must be smaller than 5 MB.");
}

$tmpPath = $file["tmp_name"] ?? "";

if (!is_uploaded_file($tmpPath)) {
    sendResponse(400, false, "Invalid uploaded file.");
}

// Verify actual file type, not just its extension.
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($tmpPath);

$allowedTypes = [
    "image/jpeg" => "jpg",
    "image/png"  => "png",
    "image/webp" => "webp"
];

if (!isset($allowedTypes[$mimeType])) {
    sendResponse(
        400,
        false,
        "Only JPG, PNG and WEBP images are supported."
    );
}

if (@getimagesize($tmpPath) === false) {
    sendResponse(400, false, "The uploaded file is not a valid image.");
}

$newFilePath = null;

try {

    // Authenticate doctor.
    $tokenStmt = $conn->prepare("
        SELECT user_id, role
        FROM AuthTokens
        WHERE token_hash = ?
          AND expires_at > UTC_TIMESTAMP()
        LIMIT 1
    ");

    $tokenStmt->execute([$tokenHash]);

    $session = $tokenStmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        sendResponse(401, false, "Invalid or expired session.");
    }

    if (strtolower($session["role"]) !== "doctor") {
        sendResponse(403, false, "Only doctors can upload Health Tip images.");
    }

    $doctorId = (int) $session["user_id"];

    // Only verified doctors can upload images.
    $doctorStmt = $conn->prepare("
        SELECT id, verification
        FROM Doctor
        WHERE id = ?
        LIMIT 1
    ");

    $doctorStmt->execute([$doctorId]);

    $doctor = $doctorStmt->fetch(PDO::FETCH_ASSOC);

    if (
        !$doctor
        || strtolower($doctor["verification"] ?? "") !== "verified"
    ) {
        sendResponse(403, false, "Only verified doctors can upload images.");
    }

    // Check article ownership.
    $articleStmt = $conn->prepare("
        SELECT resource_id
        FROM educational_resource
        WHERE resource_id = ?
          AND provider_id = ?
        LIMIT 1
    ");

    $articleStmt->execute([
        $resourceId,
        $doctorId
    ]);

    if (!$articleStmt->fetch(PDO::FETCH_ASSOC)) {
        sendResponse(
            404,
            false,
            "Health Tip not found or you do not own this article."
        );
    }

    // Create image storage directory.
    $uploadDirectory = __DIR__ . "/uploads/health_tips/";

    if (
        !is_dir($uploadDirectory)
        && !mkdir($uploadDirectory, 0755, true)
    ) {
        throw new RuntimeException("Unable to create upload directory.");
    }

    // Generate a unique and safe filename.
    $extension = $allowedTypes[$mimeType];

    $fileName = bin2hex(random_bytes(16)) . "." . $extension;

    $newFilePath = $uploadDirectory . $fileName;

    // Move uploaded image to server storage.
    if (!move_uploaded_file($tmpPath, $newFilePath)) {
        throw new RuntimeException("Unable to store uploaded image.");
    }

    // Save a relative image path in MySQL.
    $imagePath = "uploads/health_tips/" . $fileName;

    $updateStmt = $conn->prepare("
        UPDATE educational_resource
        SET image = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE resource_id = ?
          AND provider_id = ?
    ");

    $updateStmt->execute([
        $imagePath,
        $resourceId,
        $doctorId
    ]);

    if ($updateStmt->rowCount() !== 1) {
        throw new RuntimeException("Unable to update article image.");
    }

    sendResponse(
        200,
        true,
        "Health Tip image uploaded successfully.",
        [
            "resource_id" => $resourceId,
            "image" => $imagePath
        ]
    );

} catch (Throwable $e) {

    // Remove newly uploaded file if saving failed.
    if ($newFilePath !== null && is_file($newFilePath)) {
        unlink($newFilePath);
    }

    error_log("Health Tip image upload error: " . $e->getMessage());

    sendResponse(
        500,
        false,
        "Unable to upload Health Tip image."
    );
}
?>
