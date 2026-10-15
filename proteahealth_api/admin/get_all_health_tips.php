
<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

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
        'message' => 'Unauthorized. Administrator login required.'
    ]);
}

// GET requests only
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');

    respond(405, [
        'success' => false,
        'message' => 'Only GET requests are allowed.'
    ]);
}

try {
    require_once __DIR__ . '/../db.php';

    $sql = "
        SELECT *
        FROM educational_resource
        ORDER BY created_at DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $tips = $stmt->fetchAll(PDO::FETCH_ASSOC);

    respond(200, [
        'success' => true,
        'tips' => $tips,
        'total' => count($tips)
    ]);

} catch (Throwable $error) {

    error_log(
        'Get all health tips error: ' .
        $error->getMessage()
    );

    respond(500, [
        'success' => false,
        'message' => 'Unable to retrieve health tips.'
    ]);
}
