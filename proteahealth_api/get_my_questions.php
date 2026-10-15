
<?php

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");

require_once __DIR__ . "/db.php";

function sendResponse($status, $data) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Only GET requests are allowed.
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    sendResponse(405, [
        "success" => false,
        "message" => "Only GET requests are allowed."
    ]);
}

// Retrieve the authentication token.
$authorization =
    $_SERVER["HTTP_AUTHORIZATION"]
    ?? $_SERVER["REDIRECT_HTTP_AUTHORIZATION"]
    ?? "";

if ($authorization === "" && function_exists("getallheaders")) {
    foreach (getallheaders() as $name => $value) {
        if (strcasecmp($name, "Authorization") === 0) {
            $authorization = $value;
            break;
        }
    }
}

if (!preg_match(
    '/^Bearer\s+([a-f0-9]{64})$/',
    trim($authorization),
    $matches
)) {
    sendResponse(401, [
        "success" => false,
        "message" => "Authentication token missing or invalid."
    ]);
}

$tokenHash = hash("sha256", $matches[1]);

try {

    // Identify the logged-in patient.
    $authStmt = $conn->prepare("
        SELECT user_id, role
        FROM AuthTokens
        WHERE token_hash = ?
          AND expires_at > UTC_TIMESTAMP()
        LIMIT 1
    ");

    $authStmt->execute([$tokenHash]);

    $session = $authStmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        sendResponse(401, [
            "success" => false,
            "message" => "Session expired. Please log in again."
        ]);
    }

    if (strtolower($session["role"]) !== "patient") {
        sendResponse(403, [
            "success" => false,
            "message" => "Only patients can view their own questions."
        ]);
    }

    $patientId = (int) $session["user_id"];

    if ($patientId <= 0) {
        sendResponse(401, [
            "success" => false,
            "message" => "Invalid patient session."
        ]);
    }

    // Retrieve questions belonging to this patient only.
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

        INNER JOIN Patient p
            ON p.id = q.patient_id

        LEFT JOIN education_category c
            ON c.category_id = q.category_id

        LEFT JOIN question_answer a
            ON a.question_id = q.question_id

        LEFT JOIN Doctor d
            ON d.id = a.provider_id

        WHERE q.patient_id = ?

        ORDER BY q.created_at DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$patientId]);

    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($questions as &$question) {

        $question["id"] =
            (int) $question["question_id"];

        $question["question_id"] =
            (int) $question["question_id"];

        $question["is_anonymous"] =
            (bool) $question["is_anonymous"];

        if ($question["is_anonymous"]) {
            $question["userName"] = "Anonymous Patient";
        } elseif (empty($question["userName"])) {
            $question["userName"] = "Patient";
        }

        if (empty($question["category"])) {
            $question["category"] = "General health";
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

    sendResponse(200, [
        "success" => true,
        "count" => count($questions),
        "questions" => $questions
    ]);

} catch (Throwable $e) {

    error_log(
        "Get my questions error: " . $e->getMessage()
    );

    sendResponse(500, [
        "success" => false,
        "message" => "Unable to load your questions."
    ]);
}

?>
