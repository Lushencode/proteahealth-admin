
<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/db.php";

// -----------------------------------
// 1. Validate request method
// -----------------------------------

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only POST requests allowed"
    ]);
    exit;
}

// -----------------------------------
// 2. Read doctor's access token
// -----------------------------------

$authHeader = $_SERVER["HTTP_AUTHORIZATION"] ?? "";

if (!$authHeader && function_exists("getallheaders")) {
    foreach (getallheaders() as $key => $value) {
        if (strcasecmp($key, "Authorization") === 0) {
            $authHeader = $value;
            break;
        }
    }
}

if (!preg_match(
    '/^Bearer ([a-f0-9]{64})$/i',
    trim($authHeader),
    $matches
)) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Valid authentication token required"
    ]);
    exit;
}

$tokenHash = hash("sha256", $matches[1]);

// -----------------------------------
// 3. Validate patient and risk level
// -----------------------------------

$patientId = filter_input(
    INPUT_POST,
    "patient_id",
    FILTER_VALIDATE_INT
);

$riskLevel = strtolower(
    trim($_POST["risk_level"] ?? "")
);

if (
    !$patientId ||
    $patientId <= 0 ||
    !in_array($riskLevel, ["low", "medium", "high"], true)
) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid patient ID or risk level"
    ]);
    exit;
}

try {

    // -----------------------------------
    // 4. Authenticate doctor
    // -----------------------------------

    $authStmt = $conn->prepare("
        SELECT user_id
        FROM AuthTokens
        WHERE token_hash = ?
          AND role = 'doctor'
          AND expires_at > UTC_TIMESTAMP()
        LIMIT 1
    ");

    $authStmt->execute([$tokenHash]);

    $doctorId = $authStmt->fetchColumn();

    if ($doctorId === false) {
        http_response_code(401);

        echo json_encode([
            "success" => false,
            "message" => "Invalid or expired doctor token"
        ]);
        exit;
    }

    // -----------------------------------
    // 5. Verify doctor-patient assignment
    // -----------------------------------

    $assignmentStmt = $conn->prepare("
        SELECT 1
        FROM DoctorPatient
        WHERE doctor_id = ?
          AND patient_id = ?
        LIMIT 1
    ");

    $assignmentStmt->execute([
        $doctorId,
        $patientId
    ]);

    if (!$assignmentStmt->fetchColumn()) {
        http_response_code(403);

        echo json_encode([
            "success" => false,
            "message" => "Patient not assigned to this doctor"
        ]);
        exit;
    }

    // -----------------------------------
    // 6. Update patient's risk level
    // -----------------------------------

    $updateStmt = $conn->prepare("
        UPDATE Patient
        SET risk_level = ?
        WHERE id = ?
          AND EXISTS (
              SELECT 1
              FROM DoctorPatient dp
              WHERE dp.patient_id = Patient.id
                AND dp.doctor_id = ?
          )
    ");

    $updateStmt->execute([
        $riskLevel,
        $patientId,
        $doctorId
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Patient risk updated successfully",
        "risk_level" => $riskLevel
    ]);

} catch (Throwable $e) {

    error_log(
        "Patient risk update error: " . $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Internal server error"
    ]);
}
?>
