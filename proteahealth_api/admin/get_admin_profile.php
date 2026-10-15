
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

// Allow GET requests only
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');

    respond(405, [
        'success' => false,
        'message' => 'Only GET requests are allowed.'
    ]);
}

// Require an authenticated administrator
if (
    !isset($_SESSION['admin_id']) ||
    !filter_var(
        $_SESSION['admin_id'],
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    )
) {
    respond(401, [
        'success' => false,
        'message' => 'Administrator login required.'
    ]);
}

try {
    require_once __DIR__ . '/../db.php';

    $adminId = (int) $_SESSION['admin_id'];

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
        'admin' => $admin
    ]);

} catch (Throwable $error) {

    error_log(
        'Get admin profile error: ' . $error->getMessage()
    );

    respond(500, [
        'success' => false,
        'message' => 'Unable to retrieve administrator profile.'
    ]);
}
