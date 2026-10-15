
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
            p.id,
            p.name,
            p.surname,
            p.age,
            p.email,
            p.phone,
            p.gender,
            p.risk_level,
            p.doc_id,
            p.created_at,

            d.name AS doctor_name,
            d.surname AS doctor_surname

        FROM Patient AS p

        LEFT JOIN Doctor AS d
            ON p.doc_id = d.id

        ORDER BY p.id DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    respond(200, [
        'success' => true,
        'patients' => $patients,
        'total' => count($patients)
    ]);

} catch (Throwable $error) {
    error_log(
        'Get all patients error: ' . $error->getMessage()
    );

    respond(500, [
        'success' => false,
        'message' => 'Unable to retrieve patient records.'
    ]);
}
