
<?php

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");

session_name("proteahealth_admin");

session_set_cookie_params([
    "lifetime" => 0,
    "path" => "/",
    "secure" => !empty($_SERVER["HTTPS"])
        && $_SERVER["HTTPS"] !== "off",
    "httponly" => true,
    "samesite" => "Lax"
]);

session_start();

function respond(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Only accept POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Allow: POST");

    respond(405, [
        "success" => false,
        "message" => "Method not allowed"
    ]);
}

// Check administrator session
if (empty($_SESSION["admin_id"])) {
    respond(401, [
        "success" => false,
        "message" => "Unauthorized access"
    ]);
}

// Validate CSRF token
$expectedToken = $_SESSION["admin_csrf_token"] ?? "";
$providedToken = $_SERVER["HTTP_X_CSRF_TOKEN"] ?? "";

if (
    !is_string($expectedToken) ||
    !is_string($providedToken) ||
    $expectedToken === "" ||
    !hash_equals($expectedToken, $providedToken)
) {
    respond(403, [
        "success" => false,
        "message" => "Invalid security token"
    ]);
}

// Read JSON request
$input = json_decode(file_get_contents("php://input"), true);

if (!is_array($input)) {
    respond(400, [
        "success" => false,
        "message" => "Invalid JSON request"
    ]);
}

// Validate input types
foreach ([
    "decision",
    "reason",
    "credential_reference",
    "pharmacy_verification_reference"
] as $field) {
    if (
        isset($input[$field]) &&
        !is_string($input[$field])
    ) {
        respond(400, [
            "success" => false,
            "message" => "Invalid field: " . $field
        ]);
    }
}

$pharmacistId = filter_var(
    $input["pharmacist_id"] ?? null,
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 1]]
);

$decision = $input["decision"] ?? "";
$reason = trim($input["reason"] ?? "");

$credentialReference = trim(
    $input["credential_reference"] ?? ""
);

$pharmacyReference = trim(
    $input["pharmacy_verification_reference"] ?? ""
);

// Validate pharmacist ID
if (!$pharmacistId) {
    respond(400, [
        "success" => false,
        "message" => "Invalid pharmacist ID"
    ]);
}

// Validate decision
if (!in_array($decision, ["verified", "rejected"], true)) {
    respond(400, [
        "success" => false,
        "message" => "Invalid verification decision"
    ]);
}

// Validate reason
if (strlen($reason) < 10 || strlen($reason) > 2000) {
    respond(400, [
        "success" => false,
        "message" => "Reason must contain between 10 and 2000 characters"
    ]);
}

// Require professional verification reference for approval
if (
    $decision === "verified" &&
    $credentialReference === ""
) {
    respond(400, [
        "success" => false,
        "message" => "Professional registration verification reference is required"
    ]);
}

// Validate reference lengths
if (
    strlen($credentialReference) > 255 ||
    strlen($pharmacyReference) > 255
) {
    respond(400, [
        "success" => false,
        "message" => "Verification reference is too long"
    ]);
}

require_once __DIR__ . "/../db.php";

try {

    // Confirm administrator exists
    $adminStmt = $conn->prepare("
        SELECT id
        FROM Admins
        WHERE id = ?
        LIMIT 1
    ");

    $adminStmt->execute([
        $_SESSION["admin_id"]
    ]);

    if (!$adminStmt->fetchColumn()) {
        respond(401, [
            "success" => false,
            "message" => "Administrator not found"
        ]);
    }

    $adminId = (int) $_SESSION["admin_id"];

    // Start database transaction
    $conn->beginTransaction();

    // Lock pharmacist record
    $stmt = $conn->prepare("
        SELECT
            id,
            pharmacy_id,
            verification
        FROM Pharmacist
        WHERE id = ?
        FOR UPDATE
    ");

    $stmt->execute([$pharmacistId]);

    $pharmacist = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pharmacist) {
        $conn->rollBack();

        respond(404, [
            "success" => false,
            "message" => "Pharmacist not found"
        ]);
    }

    if ($pharmacist["verification"] !== "pending") {
        $conn->rollBack();

        respond(409, [
            "success" => false,
            "message" => "This pharmacist application has already been reviewed"
        ]);
    }

    $pharmacyId = null;
    $pharmacyStatus = null;
    $pharmacyUpdated = false;

    // Pharmacy checks are required for approval only
    if ($decision === "verified") {

        if (empty($pharmacist["pharmacy_id"])) {
            $conn->rollBack();

            respond(409, [
                "success" => false,
                "message" => "This pharmacist must be linked to a pharmacy before approval"
            ]);
        }

        $pharmacyId = (int) $pharmacist["pharmacy_id"];

        // Lock the linked pharmacy
        $pharmacyStmt = $conn->prepare("
            SELECT
                id,
                name,
                verification
            FROM Pharmacies
            WHERE id = ?
            FOR UPDATE
        ");

        $pharmacyStmt->execute([$pharmacyId]);

        $pharmacy = $pharmacyStmt->fetch(PDO::FETCH_ASSOC);

        if (!$pharmacy) {
            $conn->rollBack();

            respond(404, [
                "success" => false,
                "message" => "Linked pharmacy not found"
            ]);
        }

        $pharmacyStatus = $pharmacy["verification"];

        // A rejected pharmacy cannot be approved through this workflow
        if ($pharmacyStatus === "rejected") {
            $conn->rollBack();

            respond(409, [
                "success" => false,
                "message" => "The linked pharmacy has been rejected. Pharmacist approval is blocked"
            ]);
        }

        if (!in_array(
            $pharmacyStatus,
            ["pending", "verified"],
            true
        )) {
            $conn->rollBack();

            respond(409, [
                "success" => false,
                "message" => "Invalid linked pharmacy verification status"
            ]);
        }

        // Require independent pharmacy verification evidence
        if (
            $pharmacyStatus === "pending" &&
            $pharmacyReference === ""
        ) {
            $conn->rollBack();

            respond(400, [
                "success" => false,
                "message" => "A pharmacy authorization verification reference is required"
            ]);
        }

        // Approve the pharmacy only when it is pending
        if ($pharmacyStatus === "pending") {

            $updatePharmacyStmt = $conn->prepare("
                UPDATE Pharmacies
                SET verification = 'verified'
                WHERE id = ?
                  AND verification = 'pending'
            ");

            $updatePharmacyStmt->execute([$pharmacyId]);

            if ($updatePharmacyStmt->rowCount() !== 1) {
                throw new RuntimeException(
                    "Unable to update pending pharmacy"
                );
            }

            // Save pharmacy verification audit record
            $pharmacyAuditStmt = $conn->prepare("
                INSERT INTO PharmacyVerificationAudit (
                    pharmacy_id,
                    pharmacist_id,
                    admin_id,
                    previous_status,
                    new_status,
                    decision_reason,
                    credential_reference
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $pharmacyAuditStmt->execute([
                $pharmacyId,
                $pharmacistId,
                $adminId,
                $pharmacyStatus,
                "verified",
                $reason,
                $pharmacyReference
            ]);

            $pharmacyUpdated = true;
        }
    }

    // Update pharmacist verification status
    $updateStmt = $conn->prepare("
        UPDATE Pharmacist
        SET verification = ?
        WHERE id = ?
          AND verification = 'pending'
    ");

    $updateStmt->execute([
        $decision,
        $pharmacistId
    ]);

    if ($updateStmt->rowCount() !== 1) {
        throw new RuntimeException(
            "Unable to update pending pharmacist"
        );
    }

    // Record pharmacist verification decision
    $auditStmt = $conn->prepare("
        INSERT INTO PharmacistVerificationAudit (
            pharmacist_id,
            admin_id,
            previous_status,
            new_status,
            decision_reason,
            credential_reference
        )
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $auditStmt->execute([
        $pharmacistId,
        $adminId,
        $pharmacist["verification"],
        $decision,
        $reason,
        $credentialReference !== ""
            ? $credentialReference
            : null
    ]);

    // Commit pharmacist and pharmacy changes together
    $conn->commit();

    $message = "Pharmacist rejected successfully";

    if ($decision === "verified") {
        $message = $pharmacyUpdated
            ? "Pharmacist and pharmacy approved successfully"
            : "Pharmacist approved successfully. Pharmacy was already verified";
    }

    respond(200, [
        "success" => true,
        "message" => $message,
        "pharmacist_id" => $pharmacistId,
        "verification" => $decision,
        "pharmacy_id" => $pharmacyId,
        "pharmacy_updated" => $pharmacyUpdated
    ]);

} catch (Throwable $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log(
        "Pharmacist verification error: " . $e->getMessage()
    );

    respond(500, [
        "success" => false,
        "message" => "Unable to process pharmacist verification"
    ]);
}
