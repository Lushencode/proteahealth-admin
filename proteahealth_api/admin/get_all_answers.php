
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

// Verify administrator session
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
        SELECT
            a.answer_id,
            a.question_id,
            a.provider_id,
            a.answer_text,
            a.answered_at,

            q.question_text,
            q.status AS question_status,

            COALESCE(
                c.name,
                'General health'
            ) AS category,

            CASE
                WHEN d.id IS NOT NULL
                THEN CONCAT(
                    'Dr. ',
                    d.name,
                    ' ',
                    d.surname
                )
                ELSE 'Unknown Doctor'
            END AS doctor_name

        FROM question_answer AS a

        INNER JOIN patient_question AS q
            ON q.question_id = a.question_id

        LEFT JOIN Doctor AS d
            ON d.id = a.provider_id

        LEFT JOIN education_category AS c
            ON c.category_id = q.category_id

        ORDER BY a.answered_at DESC, a.answer_id DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    respond(200, [
        'success' => true,
        'answers' => $answers,
        'total' => count($answers)
    ]);

} catch (Throwable $error) {

    error_log(
        'Get all answers error: ' .
        $error->getMessage()
    );

    respond(500, [
        'success' => false,
        'message' => 'Unable to retrieve doctor answers.'
    ]);
}
