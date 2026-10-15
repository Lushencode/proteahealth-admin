
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

// POST requests only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');

    respond(405, [
        'success' => false,
        'message' => 'Only POST requests are allowed.'
    ]);
}

// Allowed development origins
// Adjust these to match your actual admin portal URL.
$allowedOrigins = [
    'http://localhost:5173',
    'http://127.0.0.1:5173',
    'http://localhost:8000',
    'http://127.0.0.1:8000',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? null;

if (
    $origin !== null &&
    !in_array($origin, $allowedOrigins, true)
) {
    respond(403, [
        'success' => false,
        'message' => 'Invalid request origin.'
    ]);
}

// Validate answer ID
$answerId = filter_var(
    $_POST['answer_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($answerId === false || $answerId === null) {
    respond(400, [
        'success' => false,
        'message' => 'A valid answer ID is required.'
    ]);
}

try {
    require_once __DIR__ . '/../db.php';

    // Delete only the selected doctor's answer
    $stmt = $conn->prepare("
        DELETE FROM question_answer
        WHERE answer_id = ?
    ");

    $stmt->execute([$answerId]);

    if ($stmt->rowCount() === 0) {
        respond(404, [
            'success' => false,
            'message' => 'Answer not found.'
        ]);
    }

    respond(200, [
        'success' => true,
        'message' => 'Doctor answer deleted successfully.'
    ]);

} catch (Throwable $error) {

    error_log(
        'Admin delete answer error: ' .
        $error->getMessage()
    );

    respond(500, [
        'success' => false,
        'message' => 'Unable to delete the doctor answer.'
    ]);
}
