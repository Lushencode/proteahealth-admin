
<?php

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");

require_once __DIR__ . "/db.php";

function sendResponse($status, $success, $message) {
    http_response_code($status);

    echo json_encode([
        "success" => $success,
        "message" => $message
    ]);

    exit;
}

// 1. Only allow POST requests.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    sendResponse(405, false, "Only POST requests are allowed.");
}

// 2. Read the Authorization header.
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

// 3. Validate the Bearer token format.
if (!preg_match(
    '/^Bearer\s+([a-f0-9]{64})$/',
    trim($authorization),
    $matches
)) {
    sendResponse(
        401,
        false,
        "Authentication token missing or invalid."
    );
}

$tokenHash = hash("sha256", $matches[1]);

// 4. Read the resource ID from form data or JSON.
$rawInput = file_get_contents("php://input");
$jsonInput = json_decode($rawInput, true);
$input = is_array($jsonInput) ? $jsonInput : [];

$resourceId = intval(
    $input["resource_id"]
    ?? $input["id"]
    ?? $_POST["resource_id"]
    ?? $_POST["id"]
    ?? 0
);

if ($resourceId <= 0) {
    sendResponse(400, false, "Invalid health tip ID.");
}

try {

    // 5. Authenticate the user.
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
        sendResponse(
            401,
            false,
            "Invalid or expired session. Please log in again."
        );
    }

    // 6. Only doctors can delete health tips.
    if (strtolower($session["role"]) !== "doctor") {
        sendResponse(
            403,
            false,
            "Only doctors can delete health tips."
        );
    }

    $doctorId = intval($session["user_id"]);

    if ($doctorId <= 0) {
        sendResponse(401, false, "Invalid doctor session.");
    }

    // 7. Verify that the doctor is approved.
    $doctorStmt = $conn->prepare("
        SELECT id, verification
        FROM Doctor
        WHERE id = ?
        LIMIT 1
    ");

    $doctorStmt->execute([$doctorId]);
    $doctor = $doctorStmt->fetch(PDO::FETCH_ASSOC);

    if (!$doctor) {
        sendResponse(403, false, "Doctor account not found.");
    }

    if (strtolower($doctor["verification"] ?? "") !== "verified") {
        sendResponse(
            403,
            false,
            "Only verified doctors can delete health tips."
        );
    }

    // 8. Begin a transaction.
    $conn->beginTransaction();

    // Lock the health tip while checking ownership.
    $resourceStmt = $conn->prepare("
        SELECT resource_id, provider_id
        FROM educational_resource
        WHERE resource_id = ?
        FOR UPDATE
    ");

    $resourceStmt->execute([$resourceId]);
    $resource = $resourceStmt->fetch(PDO::FETCH_ASSOC);

    if (!$resource) {
        $conn->rollBack();

        sendResponse(
            404,
            false,
            "Health tip not found."
        );
    }

    // 9. Only the original author can delete it.
    if (intval($resource["provider_id"]) !== $doctorId) {
        $conn->rollBack();

        sendResponse(
            403,
            false,
            "You can only delete health tips that you created."
        );
    }

    // 10. Delete the health tip.
    $deleteStmt = $conn->prepare("
        DELETE FROM educational_resource
        WHERE resource_id = ?
          AND provider_id = ?
    ");

    $deleteStmt->execute([
        $resourceId,
        $doctorId
    ]);

    if ($deleteStmt->rowCount() !== 1) {
        throw new RuntimeException("Health tip deletion failed.");
    }

    // 11. Commit the deletion.
    $conn->commit();

    sendResponse(
        200,
        true,
        "Health tip deleted successfully."
    );

} catch (Throwable $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log(
        "Delete health tip error: " . $e->getMessage()
    );

    sendResponse(
        500,
        false,
        "Unable to delete the health tip."
    );
}

?>
