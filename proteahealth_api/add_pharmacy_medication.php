
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

$medicationName = trim($_POST["medication_name"] ?? "");
$priceInput = trim((string) ($_POST["price"] ?? ""));
$stockInput = trim((string) ($_POST["stock_quantity"] ?? ""));

if ($medicationName === "" || mb_strlen($medicationName) > 200) {
    sendResponse(400, [
        "success" => false,
        "message" => "Enter a valid medication name"
    ]);
}

if (!is_numeric($priceInput) || !is_finite((float) $priceInput)
    || (float) $priceInput < 0 || (float) $priceInput > 99999999.99
    || !preg_match('/^\d+(\.\d{1,2})?$/', $priceInput)) {
    sendResponse(400, [
        "success" => false,
        "message" => "Enter a valid price with up to two decimal places"
    ]);
}

if (!ctype_digit($stockInput) || (float) $stockInput > 2147483647) {
    sendResponse(400, [
        "success" => false,
        "message" => "Enter a valid stock quantity"
    ]);
}

$stockQuantity = (int) $stockInput;
$availability = $stockQuantity > 0 ? "available" : "unavailable";

try {

    $stmt = $conn->prepare("
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

    $stmt->execute([$tokenHash]);
    $pharmacist = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pharmacist) {
        sendResponse(403, [
            "success" => false,
            "message" => "Verified pharmacist with an assigned pharmacy required"
        ]);
    }


$duplicateStmt = $conn->prepare("
    SELECT id
    FROM Pharmacy_Medication
    WHERE pharmacy_id = ?
      AND LOWER(TRIM(medication_name)) = LOWER(TRIM(?))
      AND is_active = 1
    LIMIT 1
");

$duplicateStmt->execute([
    $pharmacist["pharmacy_id"],
    $medicationName
]);

if ($duplicateStmt->fetch(PDO::FETCH_ASSOC)) {
    sendResponse(409, [
        "success" => false,
        "message" => "This medication already exists in your inventory. Please edit the existing medication instead."
    ]);
}

    $insert = $conn->prepare("
        INSERT INTO Pharmacy_Medication
            (pharmacy_id, medication_name, price, stock_quantity, availability)
        VALUES (?, ?, ?, ?, ?)
    ");

    $insert->execute([
        $pharmacist["pharmacy_id"],
        $medicationName,
        $priceInput,
        $stockQuantity,
        $availability
    ]);

    sendResponse(201, [
        "success" => true,
        "message" => "Medication added successfully",
        "medication_id" => (int) $conn->lastInsertId()
    ]);

} catch (Throwable $e) {

    error_log("Add pharmacy medication error: " . $e->getMessage());

    sendResponse(500, [
        "success" => false,
        "message" => "Unable to add medication"
    ]);
}
?>
