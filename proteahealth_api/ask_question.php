<?php
header("Content-Type: application/json; charset=utf-8");
require_once "db.php";

$raw_input = file_get_contents("php://input");
$input = json_decode($raw_input, true) ?: [];

$patient_id = intval($input["patient_id"] ?? $input["patientId"] ?? $_POST["patient_id"] ?? $_POST["patientId"] ?? 6);
$category = trim($input["category"] ?? $_POST["category"] ?? "General health");
$question_text = trim($input["question_text"] ?? $input["question"] ?? $_POST["question_text"] ?? $_POST["question"] ?? "");
$is_anonymous = (!empty($input["is_anonymous"]) || !empty($_POST["is_anonymous"])) ? 1 : 0;

if (empty($question_text)) {
    echo json_encode(["success" => false, "message" => "Question text is required"]);
    exit;
}

try {
    $cat_stmt = $conn->prepare("SELECT category_id FROM education_category WHERE name LIKE ? LIMIT 1");
    $cat_stmt->execute(["%" . $category . "%"]);
    $cat_row = $cat_stmt->fetch(PDO::FETCH_ASSOC);
    $category_id = $cat_row ? intval($cat_row['category_id']) : 1;

    $stmt = $conn->prepare("
        INSERT INTO patient_question (
            patient_id,
            category_id,
            question_text,
            is_anonymous,
            status,
            created_at
        ) VALUES (
            ?,
            ?,
            ?,
            ?,
            'open',
            NOW()
        )
    ");
    $stmt->execute([
        $patient_id > 0 ? $patient_id : 6,
        $category_id,
        $question_text,
        $is_anonymous
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Question posted successfully!",
        "question_id" => $conn->lastInsertId()
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error posting question: " . $e->getMessage()
    ]);
}
?>