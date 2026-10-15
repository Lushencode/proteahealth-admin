
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

// Require an authenticated administrator
if (!isset($_SESSION['admin_id'])) {
    respond(401, [
        'success' => false,
        'message' => 'Unauthorized. Please log in as an administrator.'
    ]);
}

// Allow POST requests only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');

    respond(405, [
        'success' => false,
        'message' => 'Only POST requests are allowed.'
    ]);
}

// Verify CSRF token
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

// Read JSON request body
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

$reason = trim((string) ($input['reason'] ?? ''));

if (!$pharmacistId || !$pharmacyId) {
    respond(400, [
        'success' => false,
        'message' => 'Valid pharmacist and pharmacy IDs are required.'
    ]);
}

$reasonLength = mb_strlen($reason, 'UTF-8');

if ($reasonLength < 10 || $reasonLength > 2000) {
    respond(400, [
        'success' => false,
        'message' => 'Reason must be between 10 and 2000 characters.'
    ]);
}

try {
    require_once __DIR__ . '/../db.php';

    $conn->beginTransaction();

    // Lock pharmacist record to prevent conflicting updates
    $stmt = $conn->prepare("
        SELECT id, pharmacy_id
        FROM Pharmacist
        WHERE id = :pharmacist_id
        FOR UPDATE
    ");

    $stmt->execute([
        ':pharmacist_id' => $pharmacistId
    ]);

    $pharmacist = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pharmacist) {
        $conn->rollBack();

        respond(404, [
            'success' => false,
            'message' => 'Pharmacist not found.'
        ]);
    }

    // Ensure the pharmacist still belongs to the selected pharmacy
    if ((int) $pharmacist['pharmacy_id'] !== (int) $pharmacyId) {
        $conn->rollBack();

        respond(409, [
            'success' => false,
            'message' => 'This pharmacist is no longer linked to the selected pharmacy.'
        ]);
    }

    // Unlink the pharmacist
    $update = $conn->prepare("
        UPDATE Pharmacist
        SET pharmacy_id = NULL
        WHERE id = :pharmacist_id
          AND pharmacy_id = :pharmacy_id
    ");

    $update->execute([
        ':pharmacist_id' => $pharmacistId,
        ':pharmacy_id' => $pharmacyId
    ]);

    if ($update->rowCount() !== 1) {
        throw new RuntimeException('Pharmacist unlink update failed.');
    }

    // Record the administrator action
    $audit = $conn->prepare("
        INSERT INTO PharmacistUnlinkAudit (
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

    $audit->execute([
        ':pharmacist_id' => $pharmacistId,
        ':pharmacy_id' => $pharmacyId,
        ':admin_id' => (int) $_SESSION['admin_id'],
        ':reason' => $reason
    ]);

    // Both operations must succeed together
    $conn->commit();

    respond(200, [
        'success' => true,
        'message' => 'Pharmacist successfully unlinked from pharmacy.',
        'pharmacist_id' => $pharmacistId,
        'pharmacy_id' => $pharmacyId
    ]);

} catch (Throwable $error) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log('Unlink pharmacist error: ' . $error->getMessage());

    respond(500, [
        'success' => false,
        'message' => 'Unable to unlink pharmacist.'
    ]);
}
