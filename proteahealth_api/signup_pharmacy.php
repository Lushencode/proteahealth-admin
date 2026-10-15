
<?php

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");
header("X-Content-Type-Options: nosniff");

require_once __DIR__ . "/db.php";

function respond(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Only POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Allow: POST");

    respond(405, [
        "success" => false,
        "message" => "Only POST requests are allowed"
    ]);
}

/*
|--------------------------------------------------------------------------
| Signup type
|--------------------------------------------------------------------------
|
| new_pharmacy      = Register a new pharmacy and pharmacist
| existing_pharmacy = Join an existing verified pharmacy
|
| Default keeps the current Android signup compatible.
|
*/

$signup_type = trim($_POST["signup_type"] ?? "new_pharmacy");

if (!in_array(
    $signup_type,
    ["new_pharmacy", "existing_pharmacy"],
    true
)) {
    respond(422, [
        "success" => false,
        "message" => "Invalid signup type"
    ]);
}

/*
|--------------------------------------------------------------------------
| Pharmacist information
|--------------------------------------------------------------------------
*/

$name = trim($_POST["name"] ?? "");
$surname = trim($_POST["surname"] ?? "");
$email = strtolower(trim($_POST["email"] ?? ""));
$phone = trim($_POST["phone"] ?? "");
$registration_code = trim($_POST["registration_code"] ?? "");
$experience_input = trim(
    $_POST["work_experience_years"] ?? ""
);

$password = $_POST["password"] ?? "";

/*
|--------------------------------------------------------------------------
| Validate pharmacist information
|--------------------------------------------------------------------------
*/

if (
    $name === "" ||
    $surname === "" ||
    $email === "" ||
    $phone === "" ||
    $registration_code === "" ||
    $experience_input === "" ||
    $password === ""
) {
    respond(422, [
        "success" => false,
        "message" => "Please complete all pharmacist fields"
    ]);
}

if (
    strlen($name) > 100 ||
    strlen($surname) > 100 ||
    strlen($email) > 150 ||
    strlen($phone) > 30 ||
    strlen($registration_code) > 100
) {
    respond(422, [
        "success" => false,
        "message" => "One or more fields exceed their allowed length"
    ]);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(422, [
        "success" => false,
        "message" => "Invalid email address"
    ]);
}

$experience = filter_var(
    $experience_input,
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 0, "max_range" => 80]]
);

if ($experience === false) {
    respond(422, [
        "success" => false,
        "message" => "Please enter valid work experience"
    ]);
}

if (
    !is_string($password) ||
    strlen($password) < 12 ||
    strlen($password) > 128
) {
    respond(422, [
        "success" => false,
        "message" =>
            "Password must be between 12 and 128 bytes"
    ]);
}

/*
|--------------------------------------------------------------------------
| Pharmacy information
|--------------------------------------------------------------------------
*/

$pharmacy_name = "";
$pharmacy_location = "";
$open_hour = "";
$close_hour = "";
$details = "";
$offers_delivery = 0;
$delivery_fee = "0.00";
$existing_pharmacy_id = null;

if ($signup_type === "new_pharmacy") {

    $pharmacy_name = trim($_POST["pharmacy_name"] ?? "");
    $pharmacy_location = trim(
        $_POST["pharmacy_location"] ?? ""
    );

    $open_hour = trim($_POST["open_hour"] ?? "");
    $close_hour = trim($_POST["close_hour"] ?? "");
    $details = trim($_POST["details"] ?? "");

    if (
        $pharmacy_name === "" ||
        $pharmacy_location === "" ||
        $open_hour === "" ||
        $close_hour === ""
    ) {
        respond(422, [
            "success" => false,
            "message" => "Please complete all pharmacy fields"
        ]);
    }

    if (
        strlen($pharmacy_name) > 200 ||
        strlen($pharmacy_location) > 255
    ) {
        respond(422, [
            "success" => false,
            "message" => "Pharmacy name or location is too long"
        ]);
    }

    // Accept HH:MM or HH:MM:SS
    $time_pattern =
        '/\A(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?\z/';

    if (
        !preg_match($time_pattern, $open_hour) ||
        !preg_match($time_pattern, $close_hour)
    ) {
        respond(422, [
            "success" => false,
            "message" => "Invalid pharmacy operating hours"
        ]);
    }

    if ($open_hour === $close_hour) {
        respond(422, [
            "success" => false,
            "message" =>
                "Opening and closing times cannot be the same"
        ]);
    }

    $delivery_input = $_POST["offers_delivery"] ?? "0";

    if (!in_array($delivery_input, ["0", "1"], true)) {
        respond(422, [
            "success" => false,
            "message" => "Invalid delivery option"
        ]);
    }

    $offers_delivery = (int) $delivery_input;

    if ($offers_delivery === 1) {
        $delivery_fee = trim(
            $_POST["delivery_fee"] ?? "0.00"
        );

        // Valid decimal value, up to 2 decimal places
        if (
            !preg_match(
                '/\A(?:0|[1-9]\d{0,7})(?:\.\d{1,2})?\z/',
                $delivery_fee
            )
        ) {
            respond(422, [
                "success" => false,
                "message" => "Invalid delivery fee"
            ]);
        }
    }

} else {

    /*
    |--------------------------------------------------------------------------
    | Existing pharmacy selection
    |--------------------------------------------------------------------------
    */

    $pharmacy_id_input = trim(
        $_POST["pharmacy_id"] ?? ""
    );

    $existing_pharmacy_id = filter_var(
        $pharmacy_id_input,
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 1]]
    );

    if ($existing_pharmacy_id === false) {
        respond(422, [
            "success" => false,
            "message" => "Please select a valid pharmacy"
        ]);
    }
}

/*
|--------------------------------------------------------------------------
| Hash password
|--------------------------------------------------------------------------
*/

$password_hash = password_hash(
    $password,
    PASSWORD_DEFAULT
);

/*
|--------------------------------------------------------------------------
| Database transaction
|--------------------------------------------------------------------------
*/

try {

    $conn->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | Check duplicate pharmacist email
    |--------------------------------------------------------------------------
    */

    $checkStmt = $conn->prepare("
        SELECT id
        FROM Pharmacist
        WHERE email = ?
        LIMIT 1
    ");

    $checkStmt->execute([$email]);

    if ($checkStmt->fetch()) {
        $conn->rollBack();

        respond(409, [
            "success" => false,
            "message" =>
                "A pharmacist account with this email already exists"
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Option 1: Register new pharmacy
    |--------------------------------------------------------------------------
    */

    if ($signup_type === "new_pharmacy") {

        $pharmacyStmt = $conn->prepare("
            INSERT INTO Pharmacies (
                name,
                location,
                open_hour,
                close_hours,
                details,
                offers_delivery,
                delivery_fee,
                verification
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
        ");

        $pharmacyStmt->execute([
            $pharmacy_name,
            $pharmacy_location,
            $open_hour,
            $close_hour,
            $details,
            $offers_delivery,
            $delivery_fee
        ]);

        $pharmacy_id = (int) $conn->lastInsertId();

    } else {

        /*
        |--------------------------------------------------------------------------
        | Option 2: Join verified pharmacy
        |--------------------------------------------------------------------------
        */

        $pharmacyStmt = $conn->prepare("
            SELECT
                id,
                location
            FROM Pharmacies
            WHERE id = ?
              AND verification = 'verified'
            LIMIT 1
            FOR UPDATE
        ");

        $pharmacyStmt->execute([
            $existing_pharmacy_id
        ]);

        $pharmacy = $pharmacyStmt->fetch(
            PDO::FETCH_ASSOC
        );

        if (!$pharmacy) {
            $conn->rollBack();

            respond(422, [
                "success" => false,
                "message" =>
                    "Selected pharmacy is unavailable or not verified"
            ]);
        }

        $pharmacy_id = (int) $pharmacy["id"];

        // Use the location stored in the database
        $pharmacy_location = $pharmacy["location"];
    }

    /*
    |--------------------------------------------------------------------------
    | Register pharmacist
    |--------------------------------------------------------------------------
    */

    $pharmacistStmt = $conn->prepare("
        INSERT INTO Pharmacist (
            name,
            surname,
            email,
            password_hash,
            pharmacy_id,
            phone,
            pharmacy_location,
            verification,
            registration_code,
            work_experience_years
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)
    ");

    $pharmacistStmt->execute([
        $name,
        $surname,
        $email,
        $password_hash,
        $pharmacy_id,
        $phone,
        $pharmacy_location,
        $registration_code,
        $experience
    ]);

    $pharmacist_id = (int) $conn->lastInsertId();

    $conn->commit();

    /*
    |--------------------------------------------------------------------------
    | Success response
    |--------------------------------------------------------------------------
    */

    respond(201, [
        "success" => true,
        "message" =>
            "Registration submitted successfully. Awaiting administrator approval.",
        "signup_type" => $signup_type,
        "pharmacy_id" => $pharmacy_id,
        "pharmacist_id" => $pharmacist_id,
        "pharmacist_verification" => "pending",
        "pharmacy_verification" =>
            $signup_type === "new_pharmacy"
                ? "pending"
                : "verified"
    ]);

} catch (PDOException $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log(
        "Pharmacist signup database error: " .
        $e->getMessage()
    );

    if (
        $e->getCode() === "23000" &&
        (int) ($e->errorInfo[1] ?? 0) === 1062
    ) {
        respond(409, [
            "success" => false,
            "message" =>
                "A pharmacist account with this email already exists"
        ]);
    }

    respond(500, [
        "success" => false,
        "message" => "Registration failed"
    ]);

} catch (Throwable $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log(
        "Pharmacist signup error: " .
        $e->getMessage()
    );

    respond(500, [
        "success" => false,
        "message" => "Registration failed"
    ]);
}
