
<?php

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Method not allowed."
    ]);

    exit;
}

require_once __DIR__ . "/db.php";

try {

    $sql = "
        SELECT
            q.question_id,
            q.question_id AS id,
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

            a.answer_id,
            a.answer_text AS doctorAnswer,
            a.answered_at,

            d.id AS doctor_id,

            CONCAT(
                'Dr. ',
                d.name,
                ' ',
                d.surname
            ) AS doctorName

        FROM patient_question q

        LEFT JOIN Patient p
            ON p.id = q.patient_id

        LEFT JOIN education_category c
            ON c.category_id = q.category_id

        LEFT JOIN question_answer a
            ON a.question_id = q.question_id

        LEFT JOIN Doctor d
            ON d.id = a.provider_id

        ORDER BY q.created_at DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($questions as &$question) {

        $question["id"] =
            (int) $question["question_id"];

        $question["question_id"] =
            (int) $question["question_id"];

        $question["is_anonymous"] =
            (bool) $question["is_anonymous"];

        if ($question["is_anonymous"]) {

            $question["userName"] =
                "Anonymous Patient";

        } elseif (empty($question["userName"])) {

            $question["userName"] = "Patient";
        }

        if (empty($question["category"])) {

            $question["category"] =
                "General health";
        }

        if (
            $question["doctorName"] === null
            || trim($question["doctorName"]) === "Dr."
        ) {

            $question["doctorName"] = "";
        }

        if ($question["doctorAnswer"] === null) {

            $question["doctorAnswer"] = "";
        }

        if ($question["answer_id"] !== null) {

            $question["answer_id"] =
                (int) $question["answer_id"];
        }

        if ($question["doctor_id"] !== null) {

            $question["doctor_id"] =
                (int) $question["doctor_id"];
        }
    }

    unset($question);

    echo json_encode([
        "success" => true,
        "questions" => $questions
    ]);

} catch (Throwable $e) {

    error_log(
        "Get questions API error: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to load questions."
    ]);
}

?>
