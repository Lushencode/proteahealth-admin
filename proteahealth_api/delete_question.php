
<?php

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");

require_once __DIR__ . "/db.php";

function sendResponse($status, $success, $message) {
    http_response_code($status);

    echo json_encode([
        "success" => $success,
        "message" => $message
    ]);

    exit;
}

// Only allow POST requests.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    sendResponse(
        405,
        false,
        "Only POST requests are allowed."
    );
}

// Get the authentication token.
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

// Validate token format.
if (!preg_match(
    '/^Bearer\s+([a-f0-9]{64})$/',
    trim($authorization),
    $matches
)) {
    sendResponse(
        401,
        false,
        "Authentication token missing or invalid."
    );
}

$tokenHash = hash("sha256", $matches[1]);

// Get the question ID.
$questionId = filter_var(
    $_POST["question_id"] ?? $_POST["id"] ?? null,
    FILTER_VALIDATE_INT
);

if (!$questionId || $questionId <= 0) {
    sendResponse(
        400,
        false,
        "Invalid question ID."
    );
}

try {

    // Step 1: Authenticate the logged-in patient.
    $tokenStmt = $conn->prepare("
        SELECT user_id, role
        FROM AuthTokens
        WHERE token_hash = ?
          AND expires_at > UTC_TIMESTAMP()
        LIMIT 1
    ");

    $tokenStmt->execute([$tokenHash]);

    $session = $tokenStmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        sendResponse(
            401,
            false,
            "Session expired. Please log in again."
        );
    }

    if (strtolower($session["role"]) !== "patient") {
        sendResponse(
            403,
            false,
            "Only patients can delete their questions."
        );
    }

    $patientId = (int) $session["user_id"];

    if ($patientId <= 0) {
        sendResponse(
            401,
            false,
            "Invalid patient session."
        );
    }

    // Step 2: Begin transaction.
    $conn->beginTransaction();

    // Step 3: Check that the question belongs
    // to the logged-in patient.
    $checkStmt = $conn->prepare("
        SELECT question_id
        FROM patient_question
        WHERE question_id = ?
          AND patient_id = ?
        FOR UPDATE
    ");

    $checkStmt->execute([
        $questionId,
        $patientId
    ]);

    $question = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$question) {

        $conn->rollBack();

        sendResponse(
            404,
            false,
            "Question not found or you do not own it."
        );
    }

    // Step 4: Delete associated doctor answers.
    $deleteAnswers = $conn->prepare("
        DELETE FROM question_answer
        WHERE question_id = ?
    ");

    $deleteAnswers->execute([$questionId]);

    // Step 5: Delete the patient's question.
    $deleteQuestion = $conn->prepare("
        DELETE FROM patient_question
        WHERE question_id = ?
          AND patient_id = ?
    ");

    $deleteQuestion->execute([
        $questionId,
        $patientId
    ]);

    if ($deleteQuestion->rowCount() !== 1) {
        throw new RuntimeException(
            "Question deletion failed."
        );
    }

    // Step 6: Save changes.
    $conn->commit();

    sendResponse(
        200,
        true,
        "Question deleted successfully."
    );

} catch (Throwable $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log(
        "Delete question error: " . $e->getMessage()
    );

    sendResponse(
        500,
        false,
        "Unable to delete the question."
    );
}

?>
