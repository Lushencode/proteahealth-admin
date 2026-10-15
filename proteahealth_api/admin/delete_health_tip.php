
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

// Only logged-in administrators can delete
if (!isset($_SESSION['admin_id'])) {
    respond(401, [
        'success' => false,
        'message' => 'Unauthorized. Administrator login required.'
    ]);
}

// Only POST requests are allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');

    respond(405, [
        'success' => false,
        'message' => 'Only POST requests are allowed.'
    ]);
}

// Validate the health tip ID
$resourceId = filter_var(
    $_POST['resource_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($resourceId === false || $resourceId === null) {
    respond(400, [
        'success' => false,
        'message' => 'A valid health tip ID is required.'
    ]);
}

// Allowed admin portal origins
$allowedOrigins = [
    'http://localhost:5173',
    'http://127.0.0.1:5173',
    'http://localhost:8000',
    'http://127.0.0.1:8000',
];

// Verify the request origin
$origin = $_SERVER['HTTP_ORIGIN'] ?? null;

if ($origin !== null) {
    if (!in_array($origin, $allowedOrigins, true)) {
        respond(403, [
            'success' => false,
            'message' => 'Invalid request origin.'
        ]);
    }
}

try {
    require_once __DIR__ . '/../db.php';

    $stmt = $conn->prepare("
        DELETE FROM educational_resource
        WHERE resource_id = ?
    ");

    $stmt->execute([$resourceId]);

    if ($stmt->rowCount() === 0) {
        respond(404, [
            'success' => false,
            'message' => 'Health tip not found.'
        ]);
    }

    respond(200, [
        'success' => true,
        'message' => 'Health tip deleted successfully.'
    ]);

} catch (Throwable $error) {
    error_log(
        'Delete health tip error: ' . $error->getMessage()
    );

    respond(500, [
        'success' => false,
        'message' => 'Unable to delete health tip.'
    ]);
}
