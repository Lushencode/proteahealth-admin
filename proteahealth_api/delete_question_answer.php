
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

// 1. Allow POST requests only.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    sendResponse(405, false, "Only POST requests are allowed.");
}

// 2. Get the Bearer token.
$authorization = $_SERVER["HTTP_AUTHORIZATION"]
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
    sendResponse(401, false, "Authentication token missing or invalid.");
}

$tokenHash = hash("sha256", $matches[1]);

// 3. Accept form data or JSON.
$rawInput = file_get_contents("php://input");
$jsonInput = json_decode($rawInput, true);
$input = is_array($jsonInput) ? $jsonInput : [];

$questionId = intval(
    $input["question_id"]
    ?? $input["id"]
    ?? $_POST["question_id"]
    ?? $_POST["id"]
    ?? 0
);

if ($questionId <= 0) {
    sendResponse(400, false, "Invalid question ID.");
}

try {

    // 4. Verify the access token and its expiry.
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
            "Invalid or expired session. Please log in again."
        );
    }

    // 5. Only doctors can delete answers.
    if (strtolower($session["role"]) !== "doctor") {
        sendResponse(
            403,
            false,
            "Only doctors can delete question answers."
        );
    }

    $doctorId = intval($session["user_id"]);

    if ($doctorId <= 0) {
        sendResponse(401, false, "Invalid doctor session.");
    }

    // 6. Confirm the doctor exists and is verified.
    $doctorStmt = $conn->prepare("
        SELECT id, verification
        FROM Doctor
        WHERE id = ?
        LIMIT 1
    ");

    $doctorStmt->execute([$doctorId]);
    $doctor = $doctorStmt->fetch(PDO::FETCH_ASSOC);

    if (!$doctor) {
        sendResponse(403, false, "Doctor account not found.");
    }

    if (strtolower($doctor["verification"] ?? "") !== "verified") {
        sendResponse(
            403,
            false,
            "Only verified doctors can delete answers."
        );
    }

    // 7. Begin a database transaction.
    $conn->beginTransaction();

    // Lock the question to prevent conflicting changes.
    $questionStmt = $conn->prepare("
        SELECT question_id
        FROM patient_question
        WHERE question_id = ?
        FOR UPDATE
    ");

    $questionStmt->execute([$questionId]);
    $question = $questionStmt->fetch(PDO::FETCH_ASSOC);

    if (!$question) {
        $conn->rollBack();
        sendResponse(404, false, "Question not found.");
    }

    // 8. Find the answer and its original doctor.
    $answerStmt = $conn->prepare("
        SELECT answer_id, provider_id
        FROM question_answer
        WHERE question_id = ?
        LIMIT 1
        FOR UPDATE
    ");

    $answerStmt->execute([$questionId]);
    $answer = $answerStmt->fetch(PDO::FETCH_ASSOC);

    if (!$answer) {
        $conn->rollBack();
        sendResponse(404, false, "No answer found for this question.");
    }

    // 9. Only the original doctor can delete the answer.
    if (intval($answer["provider_id"]) !== $doctorId) {
        $conn->rollBack();

        sendResponse(
            403,
            false,
            "You can only delete answers that you submitted."
        );
    }

    // 10. Delete the doctor's answer.
    $deleteStmt = $conn->prepare("
        DELETE FROM question_answer
        WHERE answer_id = ?
          AND provider_id = ?
    ");

    $deleteStmt->execute([
        $answer["answer_id"],
        $doctorId
    ]);

    if ($deleteStmt->rowCount() !== 1) {
        throw new RuntimeException("Answer deletion failed.");
    }

    // 11. Reopen the question.
    $updateStmt = $conn->prepare("
        UPDATE patient_question
        SET status = 'open'
        WHERE question_id = ?
    ");

    $updateStmt->execute([$questionId]);

    // 12. Commit both changes.
    $conn->commit();

    sendResponse(
        200,
        true,
        "Answer deleted successfully."
    );

} catch (Throwable $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log(
        "Delete question answer error: " . $e->getMessage()
    );

    sendResponse(
        500,
        false,
        "Unable to delete the answer."
    );
}

?>
