
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
        SELECT
            q.question_id,
            q.question_text AS question,
            q.is_anonymous,
            q.status,
            q.created_at,

            COALESCE(
                c.name,
                'General health'
            ) AS category,

            CASE
                WHEN q.is_anonymous = 1
                    THEN 'Anonymous Patient'
                ELSE CONCAT(
                    p.name,
                    ' ',
                    SUBSTRING(p.surname, 1, 1),
                    '.'
                )
            END AS userName,

            (
                SELECT COUNT(*)
                FROM question_answer AS a
                WHERE a.question_id = q.question_id
            ) AS answer_count

        FROM patient_question AS q

        LEFT JOIN Patient AS p
            ON p.id = q.patient_id

        LEFT JOIN education_category AS c
            ON c.category_id = q.category_id

        ORDER BY q.created_at DESC, q.question_id DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    respond(200, [
        'success' => true,
        'questions' => $questions,
        'total' => count($questions)
    ]);

} catch (Throwable $error) {

    error_log(
        'Get all questions error: ' .
        $error->getMessage()
    );

    respond(500, [
        'success' => false,
        'message' => 'Unable to retrieve patient questions.'
    ]);
}
