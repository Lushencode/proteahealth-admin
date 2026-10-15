
<?php

header("Content-Type: application/json; charset=utf-8");
require_once "db.php";

function sendResponse(int $status, array $data): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Only POST requests are allowed
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    sendResponse(405, [
        "success" => false,
        "message" => "Only POST requests are allowed"
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

// Read JSON request body
$input = json_decode(file_get_contents("php://input"), true);

if (!is_array($input)) {
    sendResponse(400, [
        "success" => false,
        "message" => "Invalid JSON request"
    ]);
}

$orderId = filter_var(
    $input["order_id"] ?? null,
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 1]]
);

$newStatus = $input["status"] ?? null;

if (!$orderId || !is_string($newStatus)) {
    sendResponse(400, [
        "success" => false,
        "message" => "Valid order_id and status are required"
    ]);
}

// Allowed status transitions
$transitions = [
    "pending" => "confirmed",
    "confirmed" => "processing",
    "processing" => [
        "collection" => "ready_for_collection",
        "delivery" => "shipped"
    ],
    "ready_for_collection" => "collected",
    "shipped" => "delivered"
];

try {

    // Authenticate pharmacist
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

    // Get assigned pharmacy
    $pharmacistStmt = $conn->prepare("
        SELECT p.pharmacy_id
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

    // Lock the order during validation and update
    $conn->beginTransaction();

    
$orderStmt = $conn->prepare("
    
SELECT
    id,
    patient_id,
    pharmacy_id,
    pharmacy_medication_id,
    medication_name,
    quantity,
    prescription_id,
    fulfillment_method,
    status
FROM orders
WHERE id = ?
  AND pharmacy_id = ?
FOR UPDATE

");


    $orderStmt->execute([
        $orderId,
        $pharmacist["pharmacy_id"]
    ]);

    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        $conn->rollBack();

        sendResponse(404, [
            "success" => false,
            "message" => "Order not found for your pharmacy"
        ]);
    }

    $currentStatus = $order["status"];
    $fulfillment = $order["fulfillment_method"];

    // Completed or cancelled orders cannot be updated
    if (in_array($currentStatus, [
        "collected",
        "delivered",
        "cancelled"
    ], true)) {

        $conn->rollBack();

        sendResponse(409, [
            "success" => false,
            "message" => "This order can no longer be updated"
        ]);
    }

    // Determine the next permitted status
$expectedStatus = $transitions[$currentStatus] ?? null;

if (is_array($expectedStatus)) {
    $expectedStatus = $expectedStatus[$fulfillment] ?? null;
}

// Allow cancellation before dispatch or completion
$canCancel = in_array($currentStatus, [
    "pending",
    "confirmed",
    "processing",
    "ready_for_collection"
], true);

$isCancellation = $newStatus === "cancelled" && $canCancel;

if (!$isCancellation && $newStatus !== $expectedStatus) {
    $conn->rollBack();

    sendResponse(409, [
        "success" => false,
        "message" => "Invalid status transition",
        "current_status" => $currentStatus,
        "allowed_next_status" => $expectedStatus
    ]);
}


    // Deduct pharmacy stock when an order is prepared
    // for collection or dispatched for delivery.
    if (in_array($newStatus, [
        "ready_for_collection",
        "shipped"
    ], true)) {

        $orderedQuantity = (int) $order["quantity"];

        if ($orderedQuantity <= 0) {
            throw new RuntimeException(
                "Invalid order quantity"
            );
        }

        

$stockStmt = $conn->prepare("
    UPDATE Pharmacy_Medication
    SET
        availability = CASE
            WHEN stock_quantity = ?
            THEN 'unavailable'
            ELSE 'available'
        END,
        stock_quantity = stock_quantity - ?
    WHERE id = ?
      AND pharmacy_id = ?
      AND is_active = 1
      AND availability = 'available'
      AND stock_quantity >= ?
");

$stockStmt->execute([
    $orderedQuantity,
    $orderedQuantity,
    $order["pharmacy_medication_id"],
    $pharmacist["pharmacy_id"],
    $orderedQuantity
]);


        if ($stockStmt->rowCount() !== 1) {
            $conn->rollBack();

            sendResponse(409, [
                "success" => false,
                "message" =>
                    "Insufficient stock or medication unavailable"
            ]);
        }
    }


    // Restore stock when a prepared collection order is cancelled
    if ($newStatus === "cancelled"
            && $currentStatus === "ready_for_collection") {

        $orderedQuantity = (int) $order["quantity"];

        if ($orderedQuantity <= 0) {
            throw new RuntimeException("Invalid order quantity");
        }

        

$restoreStmt = $conn->prepare("
    UPDATE Pharmacy_Medication
    SET
        stock_quantity = stock_quantity + ?,
        availability = CASE
            WHEN is_active = 1 THEN 'available'
            ELSE 'unavailable'
        END
    WHERE id = ?
      AND pharmacy_id = ?
      AND stock_quantity <= 2147483647 - ?
");

$restoreStmt->execute([
    $orderedQuantity,
    $order["pharmacy_medication_id"],
    $pharmacist["pharmacy_id"],
    $orderedQuantity
]);


        if ($restoreStmt->rowCount() !== 1) {
            throw new RuntimeException(
                "Unable to restore cancelled order stock"
            );
        }
    }

 

    // Update order
    $updateStmt = $conn->prepare("
        UPDATE orders
        SET status = ?
        WHERE id = ?
          AND pharmacy_id = ?
    ");

    
$updateStmt->execute([
    $newStatus,
    $orderId,
    $pharmacist["pharmacy_id"]
]);



// Record medication when the order is delivered or collected
if (in_array($newStatus, ["delivered", "collected"], true)) {

    $inventoryStmt = $conn->prepare("
        INSERT INTO Patient_Medication_Inventory (
            patient_id,
            order_id,
            medication_name,
            quantity_received
        )
        SELECT
            o.patient_id,
            o.id,
            o.medication_name,
            o.quantity
        FROM orders o
        WHERE o.id = ?
          AND o.pharmacy_id = ?
        ON DUPLICATE KEY UPDATE
            Patient_Medication_Inventory.id =
                Patient_Medication_Inventory.id
    ");

    $inventoryStmt->execute([
        $orderId,
        $pharmacist["pharmacy_id"]
    ]);
}


// Update prescription doses only after medication is received
if (
    in_array($newStatus, ["collected", "delivered"], true)
    && $order["prescription_id"] !== null
) {

    $receivedDoses = (int) $order["quantity"];
    $prescriptionId = (int) $order["prescription_id"];

    if ($receivedDoses <= 0) {
        throw new RuntimeException("Invalid received dose quantity");
    }

    $prescriptionStmt = $conn->prepare("
        UPDATE Prescriptions p
        INNER JOIN Pharmacy_Medication pm
            ON p.medication_id = pm.id
        SET
            p.remaining_doses = p.remaining_doses + ?,
            p.quantity = p.quantity + ?
        WHERE p.id = ?
          AND p.patient_id = ?
          AND LOWER(TRIM(pm.medication_name)) =
              LOWER(TRIM(?))
    ");

    $prescriptionStmt->execute([
        $receivedDoses,
        $receivedDoses,
        $prescriptionId,
        $order["patient_id"],
        $order["medication_name"]
    ]);

    if ($prescriptionStmt->rowCount() !== 1) {
        throw new RuntimeException(
            "Unable to update the linked prescription"
        );
    }
}




// Create a patient notification for the status update
$notificationMessages = [
    "confirmed" => "Your medication order #$orderId has been confirmed.",
    "processing" => "Your medication order #$orderId is being prepared.",
    "ready_for_collection" => "Your medication order #$orderId is ready for collection.",
    "collected" => "Your medication order #$orderId has been collected.",
    "shipped" => "Your medication order #$orderId has been dispatched for delivery.",
    "delivered" => "Your medication order #$orderId has been delivered.",
    "cancelled" => "Your medication order #$orderId has been cancelled. Please contact the pharmacy if you need assistance."
];


$notificationStmt = $conn->prepare("
    INSERT INTO notifications (
        patient_id,
        doctor_id,
        pharmacist_id,
        message,
        is_read
    )
    VALUES (
        :patient_id,
        NULL,
        NULL,
        :message,
        0
    )
");

$notificationStmt->execute([
    ':patient_id' => $order["patient_id"],
    ':message' => $notificationMessages[$newStatus]
]);


// Save the order update and notification together
$conn->commit();


    sendResponse(200, [
        "success" => true,
        "message" => "Order status updated successfully",
        "order_id" => (int) $orderId,
        "previous_status" => $currentStatus,
        "new_status" => $newStatus
    ]);

} catch (Throwable $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log("Update pharmacy order error: " . $e->getMessage());

    sendResponse(500, [
        "success" => false,
        "message" => "An internal server error occurred"
    ]);
}
