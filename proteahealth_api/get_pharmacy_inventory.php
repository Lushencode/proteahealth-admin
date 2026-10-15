
<?php

header("Content-Type: application/json; charset=utf-8");
require_once "db.php";

function sendResponse(int $status, array $data): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    sendResponse(405, [
        "success" => false,
        "message" => "Only GET requests are allowed"
    ]);
}

// Retrieve Bearer token
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

try {

    // Authenticate pharmacist and retrieve assigned pharmacy
    $stmt = $conn->prepare("
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

    $stmt->execute([$tokenHash]);

    $pharmacist = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pharmacist) {
        sendResponse(403, [
            "success" => false,
            "message" => "Verified pharmacist with an assigned pharmacy required"
        ]);
    }

    // Retrieve only medications belonging to this pharmacy
    $inventoryStmt = $conn->prepare("
        
SELECT
    id,
    pharmacy_id,
    medication_name,
    price,
    stock_quantity,
    availability,
    created_at
FROM Pharmacy_Medication
WHERE pharmacy_id = ?
  AND is_active = 1
ORDER BY medication_name ASC

    ");

    $inventoryStmt->execute([
        $pharmacist["pharmacy_id"]
    ]);

    $medications = $inventoryStmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(200, [
        "success" => true,
        "message" => "Pharmacy inventory retrieved successfully",
        "pharmacy_id" => (int) $pharmacist["pharmacy_id"],
        "total_medications" => count($medications),
        "medications" => $medications
    ]);

} catch (Throwable $e) {

    error_log("Pharmacy inventory error: " . $e->getMessage());

    sendResponse(500, [
        "success" => false,
        "message" => "Unable to retrieve pharmacy inventory"
    ]);
}
