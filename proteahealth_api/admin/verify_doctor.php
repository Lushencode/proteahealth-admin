
<?php

header("Content-Type: application/json");
header("Cache-Control: no-store");

session_name("proteahealth_admin");

session_set_cookie_params([
    "lifetime" => 0,
    "path" => "/",
    "secure" => !empty($_SERVER["HTTPS"]) &&
                $_SERVER["HTTPS"] !== "off",
    "httponly" => true,
    "samesite" => "Lax"
]);

session_start();

// Only POST requests are allowed
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed"
    ]);
    exit;
}

// Require an authenticated administrator
if (empty($_SESSION["admin_id"])) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

// Verify CSRF token
$providedToken = $_SERVER["HTTP_X_CSRF_TOKEN"] ?? "";
$sessionToken = $_SESSION["admin_csrf_token"] ?? "";

if (
    !is_string($providedToken) ||
    !is_string($sessionToken) ||
    $sessionToken === "" ||
    !hash_equals($sessionToken, $providedToken)
) {
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "message" => "Invalid security token"
    ]);
    exit;
}

// Require JSON
$contentType = $_SERVER["CONTENT_TYPE"] ?? "";

if (stripos($contentType, "application/json") !== 0) {
    http_response_code(415);
    echo json_encode([
        "success" => false,
        "message" => "JSON content required"
    ]);
    exit;
}

$data = json_decode(
    file_get_contents("php://input"),
    true
);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON"
    ]);
    exit;
}

$doctorId = $data["doctor_id"] ?? null;
$decision = $data["decision"] ?? "";
$reason = trim($data["reason"] ?? "");
$credentialReference = trim(
    $data["credential_reference"] ?? ""
);

// Validate submitted information
if (!is_int($doctorId) || $doctorId <= 0) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid doctor ID"
    ]);
    exit;
}

if (!in_array($decision, ["verified", "rejected"], true)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid verification decision"
    ]);
    exit;
}

if (strlen($reason) < 10 || strlen($reason) > 2000) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Provide a reason between 10 and 2000 characters"
    ]);
    exit;
}

// Approval requires a credential verification reference
if ($decision === "verified" && $credentialReference === "") {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Credential verification reference required"
    ]);
    exit;
}

if (strlen($credentialReference) > 255) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Credential reference is too long"
    ]);
    exit;
}

require_once __DIR__ . "/../db.php";

try {
    $conn->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    $conn->beginTransaction();

    // Confirm administrator still exists
    $adminStmt = $conn->prepare("
        SELECT id
        FROM Admins
        WHERE id = ?
        LIMIT 1
    ");

    $adminStmt->execute([$_SESSION["admin_id"]]);

    if (!$adminStmt->fetchColumn()) {
        $conn->rollBack();

        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Administrator account not found"
        ]);
        exit;
    }

    // Lock doctor record during verification
    $doctorStmt = $conn->prepare("
        SELECT id, verification
        FROM Doctor
        WHERE id = ?
        FOR UPDATE
    ");

    $doctorStmt->execute([$doctorId]);

    $doctor = $doctorStmt->fetch(PDO::FETCH_ASSOC);

    if (!$doctor) {
        $conn->rollBack();

        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Doctor not found"
        ]);
        exit;
    }

    // Only pending applications can be decided
    if ($doctor["verification"] !== "pending") {
        $conn->rollBack();

        http_response_code(409);
        echo json_encode([
            "success" => false,
            "message" => "This application has already been reviewed"
        ]);
        exit;
    }

    // Update verification status
    $updateStmt = $conn->prepare("
        UPDATE Doctor
        SET verification = ?
        WHERE id = ?
          AND verification = 'pending'
    ");

    $updateStmt->execute([
        $decision,
        $doctorId
    ]);

    if ($updateStmt->rowCount() !== 1) {
        throw new RuntimeException(
            "Doctor verification update failed"
        );
    }

    // Record administrator decision
    $auditStmt = $conn->prepare("
        INSERT INTO DoctorVerificationAudit (
            doctor_id,
            admin_id,
            previous_status,
            new_status,
            decision_reason,
            credential_reference
        )
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $auditStmt->execute([
        $doctorId,
        (int) $_SESSION["admin_id"],
        $doctor["verification"],
        $decision,
        $reason,
        $credentialReference !== ""
            ? $credentialReference
            : null
    ]);

    // Save both changes together
    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "Doctor verification updated successfully",
        "doctor_id" => $doctorId,
        "verification" => $decision
    ]);

} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log(
        "Doctor verification error: " . $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to update doctor verification"
    ]);
}
