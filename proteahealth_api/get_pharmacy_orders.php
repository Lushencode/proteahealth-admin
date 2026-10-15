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

    // Validate token and identify pharmacist
    $authStmt = $conn->prepare("
        SELECT user_id
        FROM AuthTokens
        WHERE token_hash = ?
          AND role = 'pharmacist'
          AND expires_at > UTC_TIMESTAMP()
        LIMIT 1
    ");

    $authStmt->execute([$tokenHash]);
    $auth = $authStmt->fetch(PDO::FETCH_ASSOC);

    if (!$auth) {
        sendResponse(401, [
            "success" => false,
            "message" => "Invalid or expired session"
        ]);
    }

    // Find the pharmacy assigned to this pharmacist
    $pharmacistStmt = $conn->prepare("
        SELECT p.pharmacy_id, ph.name AS pharmacy_name
        FROM Pharmacist p
        INNER JOIN Pharmacies ph
            ON ph.id = p.pharmacy_id
        WHERE p.id = ?
          AND p.verification = 'verified'
        LIMIT 1
    ");

    $pharmacistStmt->execute([$auth["user_id"]]);
    $pharmacist = $pharmacistStmt->fetch(PDO::FETCH_ASSOC);

    if (!$pharmacist) {
        sendResponse(403, [
            "success" => false,
            "message" => "Verified pharmacist with an assigned pharmacy required"
        ]);
    }


    // Optional filter for the delivery management page
    $deliveryOnly = ($_GET["fulfillment_method"] ?? "") === "delivery";


    // Retrieve orders belonging ONLY to the assigned pharmacy
    $ordersStmt = $conn->prepare("
    SELECT
        o.id,
        o.patient_id,
        CONCAT(p.name, ' ', p.surname) AS patient_name,
        o.pharmacy_id,
        o.medication_name,
        o.quantity,
        o.amount,
        o.fulfillment_method,
        o.delivery_address,
        o.delivery_fee,
        o.payment_method,
        o.order_date,
        o.status
    FROM orders o
    INNER JOIN Patient p ON p.id = o.patient_id
    
WHERE o.pharmacy_id = ?
  AND (? = 0 OR o.fulfillment_method = 'delivery')
ORDER BY o.order_date DESC, o.id DESC


");

    
$ordersStmt->execute([
    $pharmacist["pharmacy_id"],
    $deliveryOnly ? 1 : 0
]);

    $orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(200, [
        "success" => true,
        "message" => "Orders retrieved successfully",
        "pharmacy_id" => (int) $pharmacist["pharmacy_id"],
        "pharmacy_name" => $pharmacist["pharmacy_name"],
        "total_orders" => count($orders),
        "orders" => $orders
    ]);

} catch (Throwable $e) {

    error_log("Get pharmacy orders error: " . $e->getMessage());

    sendResponse(500, [
        "success" => false,
        "message" => "An internal server error occurred"
    ]);
}