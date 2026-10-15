
<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

session_name('proteahealth_admin');
session_start();

function respond(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Require administrator authentication
if (!isset($_SESSION['admin_id'])) {
    respond(401, [
        'success' => false,
        'message' => 'Unauthorized. Administrator login required.'
    ]);
}

// Allow GET requests only
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
            id,
            name,
            surname,
            email,
            specialization,
            location,
            clinic_name,
            years_in_professional,
            consultation_price,
            verification,
            created_at
        FROM Doctor
        ORDER BY id DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    respond(200, [
        'success' => true,
        'doctors' => $doctors,
        'total' => count($doctors)
    ]);

} catch (Throwable $error) {
    error_log(
        'Get all doctors error: ' . $error->getMessage()
    );

    respond(500, [
        'success' => false,
        'message' => 'Unable to retrieve doctor records.'
    ]);
}
