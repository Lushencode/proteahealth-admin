
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

$medicationIdInput = trim((string) ($_POST["medication_id"] ?? ""));
$priceInput = trim((string) ($_POST["price"] ?? ""));
$stockInput = trim((string) ($_POST["stock_quantity"] ?? ""));

if (!ctype_digit($medicationIdInput) || (int) $medicationIdInput <= 0) {
    sendResponse(400, [
        "success" => false,
        "message" => "Invalid medication ID"
    ]);
}

if (!preg_match('/^\d+(\.\d{1,2})?$/', $priceInput)
    || (float) $priceInput > 99999999.99) {
    sendResponse(400, [
        "success" => false,
        "message" => "Invalid price"
    ]);
}

if (!ctype_digit($stockInput)
    || (float) $stockInput > 2147483647) {
    sendResponse(400, [
        "success" => false,
        "message" => "Invalid stock quantity"
    ]);
}

$medicationId = (int) $medicationIdInput;
$stockQuantity = (int) $stockInput;
$availability = $stockQuantity > 0 ? "available" : "unavailable";

try {

    // Authenticate verified pharmacist
    $authStmt = $conn->prepare("
        SELECT p.pharmacy_id
        FROM AuthTokens t
        INNER JOIN Pharmacist p ON p.id = t.user_id
        INNER JOIN Pharmacies ph ON ph.id = p.pharmacy_id
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

    // Only update medications belonging to this pharmacist's pharmacy
    $updateStmt = $conn->prepare("
        UPDATE Pharmacy_Medication
        SET price = ?,
            stock_quantity = ?,
            availability = ?
        WHERE id = ?
          AND pharmacy_id = ?
    ");

    $updateStmt->execute([
        $priceInput,
        $stockQuantity,
        $availability,
        $medicationId,
        $pharmacist["pharmacy_id"]
    ]);

    // Check ownership even when the submitted values are unchanged
    $checkStmt = $conn->prepare("
        SELECT id
        FROM Pharmacy_Medication
        WHERE id = ? AND pharmacy_id = ?
        LIMIT 1
    ");

    $checkStmt->execute([
        $medicationId,
        $pharmacist["pharmacy_id"]
    ]);

    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
        sendResponse(404, [
            "success" => false,
            "message" => "Medication not found in your pharmacy"
        ]);
    }

    sendResponse(200, [
        "success" => true,
        "message" => "Medication updated successfully"
    ]);

} catch (Throwable $e) {

    error_log("Update pharmacy medication error: " . $e->getMessage());

    sendResponse(500, [
        "success" => false,
        "message" => "Unable to update medication"
    ]);
}
?>
