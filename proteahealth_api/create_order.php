<?php

header("Content-Type: application/json");

require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed"
    ]);
    exit;
}

$patientId = trim($_POST["patient_id"] ?? "");
$pharmacyId = trim($_POST["pharmacy_id"] ?? "");
$medicationName = trim($_POST["medication_name"] ?? "");
$quantity = trim($_POST["quantity"] ?? "");
$amount = trim($_POST["amount"] ?? "");
$fulfillmentMethod = strtolower(
    trim($_POST["fulfillment_method"] ?? "")
);
$deliveryAddress = trim(
    $_POST["delivery_address"] ?? ""
);
$deliveryFee = trim(
    $_POST["delivery_fee"] ?? "0"
);

$prescriptionIdInput = trim((string) ($_POST["prescription_id"] ?? ""));

if ($prescriptionIdInput !== ""
    && (!ctype_digit($prescriptionIdInput)
        || (int) $prescriptionIdInput <= 0)) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid prescription ID"
    ]);
    exit;
}

$prescriptionId = $prescriptionIdInput === ""
    ? null
    : (int) $prescriptionIdInput;



// ---------------------------------------------------------
// VALIDATION
// ---------------------------------------------------------

if (
    $patientId === "" ||
    $pharmacyId === "" ||
    $medicationName === "" ||
    $quantity === "" ||
    $amount === "" ||
    $fulfillmentMethod === ""
) {

    echo json_encode([
        "success" => false,
        "message" => "Missing required order information"
    ]);

    exit;
}


if (!in_array(
    $fulfillmentMethod,
    ["collection", "delivery"],
    true
)) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid fulfillment method"
    ]);

    exit;
}


if (
    !is_numeric($patientId) ||
    !is_numeric($pharmacyId) ||
    !is_numeric($quantity) ||
    !is_numeric($amount) ||
    !is_numeric($deliveryFee)
) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid numeric order information"
    ]);

    exit;
}


$patientId = (int)$patientId;
$pharmacyId = (int)$pharmacyId;
$quantity = (int)$quantity;
$amount = (float)$amount;
$deliveryFee = (float)$deliveryFee;


if ($quantity <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Quantity must be greater than zero"
    ]);

    exit;
}


if ($amount <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Order amount must be greater than zero"
    ]);

    exit;
}


// ---------------------------------------------------------
// DELIVERY VALIDATION
// ---------------------------------------------------------

if ($fulfillmentMethod === "delivery") {

    if ($deliveryAddress === "") {

        echo json_encode([
            "success" => false,
            "message" => "Delivery address is required"
        ]);

        exit;
    }

} else {

    $deliveryAddress = null;
    $deliveryFee = 0.00;
}


// ---------------------------------------------------------
// INSERT ORDER
// ---------------------------------------------------------

try {


    // Find the active medication in the selected pharmacy
    $medicationStmt = $conn->prepare("
        SELECT id
        FROM Pharmacy_Medication
        WHERE pharmacy_id = ?
          AND LOWER(TRIM(medication_name)) =
              LOWER(TRIM(?))
          AND is_active = 1
          AND availability = 'available'
          AND stock_quantity >= ?
    ");

    $medicationStmt->execute([
        $pharmacyId,
        $medicationName,
        $quantity
    ]);

    $matchingMedications = $medicationStmt->fetchAll(
        PDO::FETCH_ASSOC
    );

    if (count($matchingMedications) !== 1) {
        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" => count($matchingMedications) === 0
                ? "Medication unavailable or insufficient stock"
                : "Multiple active medication records found"
        ]);
        exit;
    }

    $pharmacyMedicationId =
        (int) $matchingMedications[0]["id"];



if ($prescriptionId !== null) {

    $prescriptionStmt = $conn->prepare("
        SELECT p.id
        FROM Prescriptions p
        INNER JOIN Pharmacy_Medication pm
            ON p.medication_id = pm.id
        WHERE p.id = ?
          AND p.patient_id = ?
          AND LOWER(TRIM(pm.medication_name)) = LOWER(TRIM(?))
        LIMIT 1
    ");

    $prescriptionStmt->execute([
        $prescriptionId,
        $patientId,
        $medicationName
    ]);

    if (!$prescriptionStmt->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode([
            "success" => false,
            "message" => "Prescription does not match this patient and medication"
        ]);
        exit;
    }
}


    


$stmt = $conn->prepare("
    INSERT INTO Orders (
        patient_id,
        pharmacy_id,
        pharmacy_medication_id,
        medication_name,
        quantity,
        amount,
        fulfillment_method,
        delivery_address,
        delivery_fee,
        status,
        prescription_id
    )
    VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?,
        'confirmed',
        ?
    )
");


$stmt->execute([
    $patientId,
    $pharmacyId,
    $pharmacyMedicationId,
    $medicationName,
    $quantity,
    $amount,
    $fulfillmentMethod,
    $deliveryAddress,
    $deliveryFee,
    $prescriptionId
]);







    $orderId =
        $conn->lastInsertId();


    // ---------------------------------------------------------
    // NOTIFY PHARMACIST ABOUT NEW ORDER
    // ---------------------------------------------------------

    try {

        // Find pharmacists linked to the selected pharmacy.
        // IMPORTANT: This assumes Pharmacist has a pharmacy_id column.
        $pharmacistStmt = $conn->prepare("
            SELECT id
            FROM Pharmacist
            WHERE pharmacy_id = ?
        ");

        $pharmacistStmt->execute([$pharmacyId]);

        $pharmacists = $pharmacistStmt->fetchAll(
            PDO::FETCH_ASSOC
        );

        $notificationStmt = $conn->prepare("
            INSERT INTO notifications (
                patient_id,
                doctor_id,
                pharmacist_id,
                message,
                is_read
            )
            VALUES (
                NULL,
                NULL,
                ?,
                ?,
                0
            )
        ");

        foreach ($pharmacists as $pharmacist) {

            $pharmacistId = (int) $pharmacist["id"];

            $message =
                "New Order #{$orderId}: "
                . "{$medicationName} "
                . "(Quantity: {$quantity}) "
                . "- {$fulfillmentMethod}.";

            $notificationStmt->execute([
                $pharmacistId,
                $message
            ]);
        }

    } catch (PDOException $notificationError) {

        // Do not fail an already-created order because
        // its notification could not be saved.
        error_log(
            "Order notification error: "
            . $notificationError->getMessage()
        );
    }



    echo json_encode([
        "success" => true,
        "message" => "Order placed successfully",
        "order_id" => (int)$orderId
    ]);

    


}  catch (PDOException $e) {

    error_log(
        "Create order SQL error: " . $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to place order"
    ]);
}

?>