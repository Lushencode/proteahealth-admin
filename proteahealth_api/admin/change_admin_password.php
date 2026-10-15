
<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

session_name('proteahealth_admin');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) &&
                $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

function respond(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Require POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');

    respond(405, [
        'success' => false,
        'message' => 'Only POST requests are allowed.'
    ]);
}

// Require an authenticated administrator
$adminId = filter_var(
    $_SESSION['admin_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($adminId === false || $adminId === null) {
    respond(401, [
        'success' => false,
        'message' => 'Administrator login required.'
    ]);
}

// Verify CSRF token
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
$sessionToken = $_SESSION['admin_csrf_token'] ?? '';

if (
    !is_string($csrfToken) ||
    !is_string($sessionToken) ||
    $sessionToken === '' ||
    !hash_equals($sessionToken, $csrfToken)
) {
    respond(403, [
        'success' => false,
        'message' => 'Invalid CSRF token.'
    ]);
}

// Require JSON
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (!preg_match('~^application/json(?:\s*;|$)~i', $contentType)) {
    respond(415, [
        'success' => false,
        'message' => 'Content-Type must be application/json.'
    ]);
}

// Limit request body size
if (
    isset($_SERVER['CONTENT_LENGTH']) &&
    (int) $_SERVER['CONTENT_LENGTH'] > 8192
) {
    respond(413, [
        'success' => false,
        'message' => 'Request is too large.'
    ]);
}

// Read JSON
$input = json_decode(
    file_get_contents('php://input'),
    true
);

if (
    !is_array($input) ||
    json_last_error() !== JSON_ERROR_NONE
) {
    respond(400, [
        'success' => false,
        'message' => 'Invalid JSON request.'
    ]);
}

$currentPassword = $input['current_password'] ?? null;
$newPassword = $input['new_password'] ?? null;

// Validate input types
if (
    !is_string($currentPassword) ||
    !is_string($newPassword)
) {
    respond(422, [
        'success' => false,
        'message' => 'Both password fields are required.'
    ]);
}

// Do not trim passwords: spaces may be intentional
if ($currentPassword === '') {
    respond(422, [
        'success' => false,
        'message' => 'Current password is required.'
    ]);
}

// Password length: 12-128 bytes
if (
    strlen($newPassword) < 12 ||
    strlen($newPassword) > 128
) {
    respond(422, [
        'success' => false,
        'message' =>
            'New password must be between 12 and 128 bytes.'
    ]);
}

// New password must differ from current password
if (hash_equals($currentPassword, $newPassword)) {
    respond(422, [
        'success' => false,
        'message' =>
            'New password must differ from your current password.'
    ]);
}

try {
    require_once __DIR__ . '/../db.php';

    $conn->beginTransaction();

    // Lock the administrator record during password change
    $stmt = $conn->prepare("
        SELECT id, password
        FROM Admins
        WHERE id = :admin_id
        LIMIT 1
        FOR UPDATE
    ");

    $stmt->execute([
        ':admin_id' => $adminId
    ]);

    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        $conn->rollBack();

        respond(401, [
            'success' => false,
            'message' => 'Administrator account not found.'
        ]);
    }

    // Verify current password
    if (!password_verify(
        $currentPassword,
        $admin['password']
    )) {
        $conn->rollBack();

        respond(401, [
            'success' => false,
            'message' => 'Current password is incorrect.'
        ]);
    }

    // Hash the new password securely
    $newPasswordHash = password_hash(
        $newPassword,
        PASSWORD_DEFAULT
    );

    // Update password
    $stmt = $conn->prepare("
        UPDATE Admins
        SET password = :password
        WHERE id = :admin_id
    ");

    $stmt->execute([
        ':password' => $newPasswordHash,
        ':admin_id' => $adminId
    ]);

    $conn->commit();

    // Rotate the current session ID
    session_regenerate_id(true);

    respond(200, [
        'success' => true,
        'message' => 'Password changed successfully.'
    ]);

} catch (Throwable $error) {

    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log(
        'Change admin password error: ' .
        $error->getMessage()
    );

    respond(500, [
        'success' => false,
        'message' => 'Unable to change administrator password.'
    ]);
}
