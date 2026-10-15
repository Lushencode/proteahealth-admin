
<?php

header('Content-Type: application/json; charset=utf-8');

session_name('proteahealth_admin');
session_start();

function respond(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Administrator authentication
if (!isset($_SESSION['admin_id'])) {
    respond(401, [
        'success' => false,
        'message' => 'Unauthorized. Please log in as an administrator.'
    ]);
}

// POST requests only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');

    respond(405, [
        'success' => false,
        'message' => 'Only POST requests are allowed.'
    ]);
}

// CSRF protection
$expectedToken = $_SESSION['admin_csrf_token'] ?? '';
$receivedToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (
    !is_string($expectedToken) ||
    !is_string($receivedToken) ||
    $expectedToken === '' ||
    !hash_equals($expectedToken, $receivedToken)
) {
    respond(403, [
        'success' => false,
        'message' => 'Invalid CSRF token.'
    ]);
}

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    respond(400, [
        'success' => false,
        'message' => 'Invalid JSON request.'
    ]);
}

$pharmacistId = filter_var(
    $input['pharmacist_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$pharmacyId = filter_var(
    $input['pharmacy_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$reasonInput = $input['reason'] ?? null;
$reason = is_string($reasonInput) ? trim($reasonInput) : '';

if (!$pharmacistId || !$pharmacyId) {
    respond(400, [
        'success' => false,
        'message' => 'Valid pharmacist and pharmacy IDs are required.'
    ]);
}

if (mb_strlen($reason, 'UTF-8') < 10 ||
    mb_strlen($reason, 'UTF-8') > 2000) {
    respond(400, [
        'success' => false,
        'message' => 'Reason must be between 10 and 2000 characters.'
    ]);
}

try {
    require_once __DIR__ . '/../db.php';

    $conn->beginTransaction();

    // Lock pharmacist to prevent concurrent assignments
    $pharmacistStmt = $conn->prepare("
        SELECT id, pharmacy_id, verification
        FROM Pharmacist
        WHERE id = :id
        FOR UPDATE
    ");

    $pharmacistStmt->execute([
        ':id' => $pharmacistId
    ]);

    $pharmacist = $pharmacistStmt->fetch(PDO::FETCH_ASSOC);

    if (!$pharmacist) {
        $conn->rollBack();

        respond(404, [
            'success' => false,
            'message' => 'Pharmacist not found.'
        ]);
    }

    if ($pharmacist['verification'] !== 'verified') {
        $conn->rollBack();

        respond(409, [
            'success' => false,
            'message' => 'Only verified pharmacists can be linked.'
        ]);
    }

    if ($pharmacist['pharmacy_id'] !== null) {
        $conn->rollBack();

        respond(409, [
            'success' => false,
            'message' => 'This pharmacist is already linked to a pharmacy.'
        ]);
    }

    // Lock and validate selected pharmacy
    $pharmacyStmt = $conn->prepare("
        SELECT id, verification
        FROM Pharmacies
        WHERE id = :id
        FOR UPDATE
    ");

    $pharmacyStmt->execute([
        ':id' => $pharmacyId
    ]);

    $pharmacy = $pharmacyStmt->fetch(PDO::FETCH_ASSOC);

    if (!$pharmacy) {
        $conn->rollBack();

        respond(404, [
            'success' => false,
            'message' => 'Selected pharmacy not found.'
        ]);
    }

    if ($pharmacy['verification'] !== 'verified') {
        $conn->rollBack();

        respond(409, [
            'success' => false,
            'message' => 'Only verified pharmacies can be linked.'
        ]);
    }

    // Link pharmacist to pharmacy
    $updateStmt = $conn->prepare("
        UPDATE Pharmacist
        SET pharmacy_id = :pharmacy_id
        WHERE id = :pharmacist_id
          AND pharmacy_id IS NULL
          AND verification = 'verified'
    ");

    $updateStmt->execute([
        ':pharmacy_id' => $pharmacyId,
        ':pharmacist_id' => $pharmacistId
    ]);

    if ($updateStmt->rowCount() !== 1) {
        throw new RuntimeException('Pharmacist link update failed.');
    }

    // Record the administrator action
    $auditStmt = $conn->prepare("
        INSERT INTO PharmacistLinkAudit (
            pharmacist_id,
            pharmacy_id,
            admin_id,
            reason
        )
        VALUES (
            :pharmacist_id,
            :pharmacy_id,
            :admin_id,
            :reason
        )
    ");

    $auditStmt->execute([
        ':pharmacist_id' => $pharmacistId,
        ':pharmacy_id' => $pharmacyId,
        ':admin_id' => (int) $_SESSION['admin_id'],
        ':reason' => $reason
    ]);

    // Both changes succeed together
    $conn->commit();

    respond(200, [
        'success' => true,
        'message' => 'Pharmacist successfully linked to pharmacy.',
        'pharmacist_id' => $pharmacistId,
        'pharmacy_id' => $pharmacyId
    ]);

} catch (Throwable $error) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log('Link pharmacist error: ' . $error->getMessage());

    respond(500, [
        'success' => false,
        'message' => 'Unable to link pharmacist to pharmacy.'
    ]);
}
