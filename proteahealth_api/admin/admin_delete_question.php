
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

// Allowed admin portal origins
$allowedOrigins = [
    'http://localhost:5173',
    'http://127.0.0.1:5173',
    'http://localhost:8000',
    'http://127.0.0.1:8000',
];

// Check request origin
$origin = $_SERVER['HTTP_ORIGIN'] ?? null;

if ($origin !== null) {
    if (!in_array($origin, $allowedOrigins, true)) {
        respond(403, [
            'success' => false,
            'message' => 'Invalid request origin.'
        ]);
    }
}

// Validate question ID
$questionId = filter_var(
    $_POST['question_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($questionId === false || $questionId === null) {
    respond(400, [
        'success' => false,
        'message' => 'A valid question ID is required.'
    ]);
}

try {
    require_once __DIR__ . '/../db.php';

    $conn->beginTransaction();

    // Check that the question exists
    $checkStmt = $conn->prepare("
        SELECT question_id
        FROM patient_question
        WHERE question_id = ?
        FOR UPDATE
    ");

    $checkStmt->execute([$questionId]);

    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
        $conn->rollBack();

        respond(404, [
            'success' => false,
            'message' => 'Question not found.'
        ]);
    }

    // Delete doctors' answers first
    $deleteAnswers = $conn->prepare("
        DELETE FROM question_answer
        WHERE question_id = ?
    ");

    $deleteAnswers->execute([$questionId]);

    // Delete the patient question
    $deleteQuestion = $conn->prepare("
        DELETE FROM patient_question
        WHERE question_id = ?
    ");

    $deleteQuestion->execute([$questionId]);

    // Save changes
    $conn->commit();

    respond(200, [
        'success' => true,
        'message' => 'Question and associated answers deleted successfully.'
    ]);

} catch (Throwable $error) {

    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log(
        'Admin delete question error: ' .
        $error->getMessage()
    );

    respond(500, [
        'success' => false,
        'message' => 'Unable to delete question.'
    ]);
}
