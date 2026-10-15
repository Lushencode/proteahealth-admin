
<?php

header("Content-Type: application/json; charset=utf-8");
require_once "db.php";

function sendResponse(int $status, array $data): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    sendResponse(405, [
        "success" => false,
        "message" => "Only POST requests are allowed"
    ]);
}

// Get pharmacist authentication token
$authorization = $_SERVER["HTTP_AUTHORIZATION"] ?? "";

if (!$authorization && function_exists("getallheaders")) {
    foreach (getallheaders() as $key => $value) {
        if (strcasecmp($key, "Authorization") === 0) {
            $authorization = $value;
            break;
        }
    }
}

if (!preg_match('/^Bearer\s+([a-f0-9]{64})$/i', trim($authorization), $matches)) {
    sendResponse(401, [
        "success" => false,
        "message" => "Authentication required"
    ]);
}

$tokenHash = hash("sha256", $matches[1]);

// Validate medication ID
$medicationIdInput = trim((string) ($_POST["medication_id"] ?? ""));

if (!ctype_digit($medicationIdInput)
    || (float) $medicationIdInput > 2147483647
    || (int) $medicationIdInput <= 0) {

    sendResponse(400, [
        "success" => false,
        "message" => "Invalid medication ID"
    ]);
}

$medicationId = (int) $medicationIdInput;

try {

    // Authenticate pharmacist and identify their pharmacy
    $authStmt = $conn->prepare("
        SELECT p.pharmacy_id
        FROM AuthTokens t
        INNER JOIN Pharmacist p
            ON p.id = t.user_id
        INNER JOIN Pharmacies ph
            ON ph.id = p.pharmacy_id
        WHERE t.token_hash = ?
          AND t.role = 'pharmacist'
          AND t.expires_at > UTC_TIMESTAMP()
          AND p.verification = 'verified'
        LIMIT 1
    ");

    $authStmt->execute([$tokenHash]);
    $pharmacist = $authStmt->fetch(PDO::FETCH_ASSOC);

    if (!$pharmacist) {
        sendResponse(403, [
            "success" => false,
            "message" => "Verified pharmacist required"
        ]);
    }

    // Soft delete only medications owned by this pharmacy
    $removeStmt = $conn->prepare("
        UPDATE Pharmacy_Medication
        SET is_active = 0,
            availability = 'unavailable'
        WHERE id = ?
          AND pharmacy_id = ?
          AND is_active = 1
    ");

    $removeStmt->execute([
        $medicationId,
        $pharmacist["pharmacy_id"]
    ]);

    if ($removeStmt->rowCount() === 0) {
        sendResponse(404, [
            "success" => false,
            "message" => "Active medication not found in your pharmacy"
        ]);
    }

    sendResponse(200, [
        "success" => true,
        "message" => "Medication removed successfully"
    ]);

} catch (Throwable $e) {

    error_log("Remove pharmacy medication error: " . $e->getMessage());

    sendResponse(500, [
        "success" => false,
        "message" => "Unable to remove medication"
    ]);
}
?>
