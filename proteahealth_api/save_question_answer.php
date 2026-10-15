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

// 2. Read the Authorization header.
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

// 3. Require a valid Bearer token format.
if (!preg_match('/^Bearer\s+([a-f0-9]{64})$/', trim($authorization), $matches)) {
    sendResponse(401, false, "Authentication token missing or invalid.");
}

$accessToken = $matches[1];
$tokenHash = hash("sha256", $accessToken);

// 4. Read JSON or form-encoded input.
$rawInput = file_get_contents("php://input");
$jsonInput = json_decode($rawInput, true);
$input = is_array($jsonInput) ? $jsonInput : [];

$questionId = intval(
    $input["question_id"]
    ?? $input["questionId"]
    ?? $_POST["question_id"]
    ?? $_POST["questionId"]
    ?? 0
);

$answerText = trim(
    $input["answer_text"]
    ?? $input["answer"]
    ?? $_POST["answer_text"]
    ?? $_POST["answer"]
    ?? ""
);

// Optional compatibility check with existing Android API.
$submittedDoctorId = intval(
    $input["provider_id"]
    ?? $input["doctor_id"]
    ?? $_POST["provider_id"]
    ?? $_POST["doctor_id"]
    ?? 0
);

if ($questionId <= 0 || $answerText === "") {
    sendResponse(
        400,
        false,
        "Valid question ID and answer text are required."
    );
}

if (mb_strlen($answerText, "UTF-8") > 10000) {
    sendResponse(400, false, "Answer is too long.");
}

try {

    // 5. Authenticate using the existing AuthTokens table.
    $tokenStmt = $conn->prepare("
        SELECT user_id, role, expires_at
        FROM AuthTokens
        WHERE token_hash = ?
        LIMIT 1
    ");

    $tokenStmt->execute([$tokenHash]);
    $tokenRecord = $tokenStmt->fetch(PDO::FETCH_ASSOC);

    if (!$tokenRecord) {
        sendResponse(401, false, "Invalid authentication token.");
    }

    $expiry = new DateTimeImmutable(
        $tokenRecord["expires_at"],
        new DateTimeZone("UTC")
    );

    $now = new DateTimeImmutable(
        "now",
        new DateTimeZone("UTC")
    );

    if ($expiry <= $now) {
        sendResponse(401, false, "Session expired. Please log in again.");
    }

    // 6. Only doctor accounts can submit answers.
    if (strtolower($tokenRecord["role"]) !== "doctor") {
        sendResponse(
            403,
            false,
            "Only doctors can answer patient questions."
        );
    }

    // Never trust a doctor ID supplied by Android.
    $doctorId = intval($tokenRecord["user_id"]);

    if ($doctorId <= 0) {
        sendResponse(401, false, "Invalid doctor session.");
    }

    // Reject requests attempting to use another doctor's ID.
    if (
        $submittedDoctorId > 0 &&
        $submittedDoctorId !== $doctorId
    ) {
        sendResponse(
            403,
            false,
            "Doctor identity does not match the session."
        );
    }

    // 7. Confirm the doctor exists and is verified.
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
            "Your doctor account must be verified to answer questions."
        );
    }

    // 8. Save changes atomically.
    $conn->beginTransaction();

    // Lock the question while updating its answer.
    $questionStmt = $conn->prepare("
        SELECT question_id, status
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

    if ($question["status"] === "closed") {
        $conn->rollBack();
        sendResponse(409, false, "This question is closed.");
    }

    // 9. Check whether an answer already exists.
    $checkStmt = $conn->prepare("
        SELECT answer_id, provider_id
        FROM question_answer
        WHERE question_id = ?
        LIMIT 1
        FOR UPDATE
    ");

    $checkStmt->execute([$questionId]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {

        // Only the original doctor can edit their answer.
        if (intval($existing["provider_id"]) !== $doctorId) {
            $conn->rollBack();

            sendResponse(
                403,
                false,
                "Only the doctor who wrote this answer can edit it."
            );
        }

        $updateStmt = $conn->prepare("
            UPDATE question_answer
            SET answer_text = ?,
                answered_at = UTC_TIMESTAMP()
            WHERE answer_id = ?
              AND provider_id = ?
        ");

        $updateStmt->execute([
            $answerText,
            $existing["answer_id"],
            $doctorId
        ]);

    } else {

        // Create a new answer.
        $insertStmt = $conn->prepare("
            INSERT INTO question_answer
                (question_id, provider_id, answer_text, answered_at)
            VALUES
                (?, ?, ?, UTC_TIMESTAMP())
        ");

        $insertStmt->execute([
            $questionId,
            $doctorId,
            $answerText
        ]);
    }

    // 10. Update the question status.
    $statusStmt = $conn->prepare("
        UPDATE patient_question
        SET status = 'answered'
        WHERE question_id = ?
    ");

    $statusStmt->execute([$questionId]);

    $conn->commit();

    sendResponse(
        200,
        true,
        "Answer saved successfully."
    );

} catch (Throwable $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log(
        "Save question answer error: " . $e->getMessage()
    );

    sendResponse(
        500,
        false,
        "Unable to save the answer."
    );
}

?>
