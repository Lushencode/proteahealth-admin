
<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/db.php";

// Only allow GET requests
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only GET requests allowed"
    ]);
    exit;
}

// Get doctor's access token
$authHeader = $_SERVER["HTTP_AUTHORIZATION"] ?? "";

if (!$authHeader && function_exists("getallheaders")) {
    foreach (getallheaders() as $key => $value) {
        if (strcasecmp($key, "Authorization") === 0) {
            $authHeader = $value;
            break;
        }
    }
}

// Validate Bearer token
if (!preg_match(
    '/^Bearer ([a-f0-9]{64})$/i',
    trim($authHeader),
    $matches
)) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Authentication token required"
    ]);
    exit;
}

$tokenHash = hash("sha256", $matches[1]);

try {

    // Identify authenticated doctor
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

    // Load only patients assigned to this doctor
    


    $stmt = $conn->prepare("
        SELECT
            p.id,
            p.name,
            p.surname,
            p.email,
            p.age,
            p.phone,
            p.gender,
            p.home_address AS address,
            p.emergency_contact_name AS emergencyName,
            p.emergency_contact_number AS emergencyNumber,
            p.emergency_contact_relationship AS emergencyRelationship,
            p.allergies,
            p.medical_conditions AS medicalConditions,
            p.risk_level AS riskLevel
        FROM Patient p
        WHERE EXISTS (
            SELECT 1
            FROM DoctorPatient dp
            WHERE dp.patient_id = p.id
              AND dp.doctor_id = ?
        )
        ORDER BY p.id DESC
    ");

    $stmt->execute([$doctorId]);

    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);


    $stmt->execute([$doctorId]);

    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Preserve existing patient formatting
    foreach ($patients as &$p) {

        $p["id"] = (string)$p["id"];

        $p["age"] = (string)($p["age"] ?? "N/A");

        if (empty($p["emergencyName"])) {
            $p["emergencyName"] = "Emergency Contact";
        }

        if (empty($p["emergencyNumber"])) {
            $p["emergencyNumber"] = "N/A";
        }

        if (empty($p["emergencyRelationship"])) {
            $p["emergencyRelationship"] = "Family";
        }

        if (empty($p["allergies"])) {
            $p["allergies"] = "None Reported";
        }

        if (empty($p["medicalConditions"])) {
            $p["medicalConditions"] = "None";
        }
    }

    unset($p);

    echo json_encode([
        "success" => true,
        "patients" => $patients
    ]);

} catch (Throwable $e) {

    error_log("Get patients error: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Internal server error"
    ]);
}
?>
