
<?php

header("Content-Type: application/json; charset=utf-8");
require_once "db.php";

function sendResponse(int $status, array $data): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Only GET requests are allowed
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    sendResponse(405, [
        "success" => false,
        "message" => "Only GET requests are allowed",
        "medications" => []
    ]);
}

// Retrieve Authorization header
$authorization = $_SERVER["HTTP_AUTHORIZATION"] ?? "";

if (!$authorization && function_exists("getallheaders")) {
    foreach (getallheaders() as $key => $value) {
        if (strcasecmp($key, "Authorization") === 0) {
            $authorization = $value;
            break;
        }
    }
}

// Validate Bearer token format
if (!preg_match(
    '/^Bearer\s+([a-f0-9]{64})$/i',
    trim($authorization),
    $matches
)) {
    sendResponse(401, [
        "success" => false,
        "message" => "Authentication required",
        "medications" => []
    ]);
}

$tokenHash = hash("sha256", $matches[1]);

try {

    // Authenticate the logged-in patient
    $authStmt = $conn->prepare("
        SELECT user_id
        FROM AuthTokens
        WHERE token_hash = ?
          AND role = 'patient'
          AND expires_at > UTC_TIMESTAMP()
        LIMIT 1
    ");

    $authStmt->execute([$tokenHash]);

    $auth = $authStmt->fetch(PDO::FETCH_ASSOC);

    if (!$auth) {
        sendResponse(401, [
            "success" => false,
            "message" => "Invalid or expired session",
            "medications" => []
        ]);
    }

    $patientId = (int) $auth["user_id"];

    // Retrieve received medications for this patient only
    
$inventoryStmt = $conn->prepare("
    SELECT
        i.id,
        i.order_id,
        i.medication_name,
        i.quantity_received,
        i.received_at,
        ph.name AS pharmacy_name

    FROM Patient_Medication_Inventory i

    INNER JOIN orders o
        ON i.order_id = o.id

    INNER JOIN Pharmacies ph
        ON o.pharmacy_id = ph.id

    WHERE i.patient_id = ?

    ORDER BY i.received_at DESC, i.id DESC
");

$inventoryStmt->execute([$patientId]);

$medications = $inventoryStmt->fetchAll(PDO::FETCH_ASSOC);


    sendResponse(200, [
        "success" => true,
        "message" => "Received medications retrieved successfully",
        "total_medications" => count($medications),
        "medications" => $medications
    ]);

} catch (Throwable $e) {

    error_log(
        "Get patient medication inventory error: " .
        $e->getMessage()
    );

    sendResponse(500, [
        "success" => false,
        "message" => "An internal server error occurred",
        "medications" => []
    ]);
}
