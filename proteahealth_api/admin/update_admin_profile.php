
<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

session_name('proteahealth_admin');
session_start();

function respond(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Only POST requests are allowed
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

if (
    !preg_match(
        '~^application/json(?:\s*;|$)~i',
        $contentType
    )
) {
    respond(415, [
        'success' => false,
        'message' => 'Content-Type must be application/json.'
    ]);
}

// Read request body
$input = json_decode(
    file_get_contents('php://input'),
    true
);

if (!is_array($input) || json_last_error() !== JSON_ERROR_NONE) {
    respond(400, [
        'success' => false,
        'message' => 'Invalid JSON request.'
    ]);
}

$name = $input['name'] ?? null;
$surname = $input['surname'] ?? null;
$email = $input['email'] ?? null;

// Validate types
if (
    !is_string($name) ||
    !is_string($surname) ||
    !is_string($email)
) {
    respond(422, [
        'success' => false,
        'message' => 'Name, surname and email must be text.'
    ]);
}

$name = trim($name);
$surname = trim($surname);
$email = strtolower(trim($email));

// Validate names: 1 to 100 Unicode characters
if (
    !preg_match('/\A.{1,100}\z/us', $name) ||
    !preg_match('/\A.{1,100}\z/us', $surname)
) {
    respond(422, [
        'success' => false,
        'message' => 'Name and surname must contain 1 to 100 characters.'
    ]);
}

// Validate email
if (
    strlen($email) > 150 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL)
) {
    respond(422, [
        'success' => false,
        'message' => 'Please enter a valid email address.'
    ]);
}

try {
    require_once __DIR__ . '/../db.php';

    // Update only the authenticated administrator
    $stmt = $conn->prepare("
        UPDATE Admins
        SET
            name = :name,
            surname = :surname,
            email = :email
        WHERE id = :admin_id
    ");

    $stmt->execute([
        ':name' => $name,
        ':surname' => $surname,
        ':email' => $email,
        ':admin_id' => $adminId
    ]);

    // Retrieve the updated account
    $stmt = $conn->prepare("
        SELECT
            id,
            name,
            surname,
            email
        FROM Admins
        WHERE id = :admin_id
        LIMIT 1
    ");

    $stmt->execute([
        ':admin_id' => $adminId
    ]);

    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        respond(401, [
            'success' => false,
            'message' => 'Administrator account not found.'
        ]);
    }

    respond(200, [
        'success' => true,
        'message' => 'Administrator profile updated successfully.',
        'admin' => $admin
    ]);

} catch (PDOException $error) {

    // Handle duplicate email addresses
    if (
        $error->getCode() === '23000' &&
        (int) ($error->errorInfo[1] ?? 0) === 1062
    ) {
        respond(409, [
            'success' => false,
            'message' => 'This email address is already registered.'
        ]);
    }

    error_log(
        'Update admin profile database error: ' .
        $error->getMessage()
    );

    respond(500, [
        'success' => false,
        'message' => 'Unable to update administrator profile.'
    ]);

} catch (Throwable $error) {

    error_log(
        'Update admin profile error: ' .
        $error->getMessage()
    );

    respond(500, [
        'success' => false,
        'message' => 'Unable to update administrator profile.'
    ]);
}
