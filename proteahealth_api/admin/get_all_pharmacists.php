
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

// Require administrator authentication
if (!isset($_SESSION['admin_id'])) {
    respond(401, [
        'success' => false,
        'message' => 'Unauthorized. Please log in as an administrator.'
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
            ph.id,
            ph.name,
            ph.surname,
            ph.email,
            ph.phone,
            ph.pharmacy_location,
            ph.work_experience_years,
            ph.verification,
            ph.pharmacy_id,
            ph.created_at,

            p.name AS pharmacy_name,
            p.location AS linked_pharmacy_location,
            p.verification AS pharmacy_verification

        FROM Pharmacist AS ph

        LEFT JOIN Pharmacies AS p
            ON ph.pharmacy_id = p.id

        ORDER BY ph.id DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $pharmacists = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'pharmacists' => $pharmacists,
        'total' => count($pharmacists)
    ]);

} catch (Throwable $error) {
    error_log(
        'Get all pharmacists error: ' . $error->getMessage()
    );

    respond(500, [
        'success' => false,
        'message' => 'Unable to retrieve pharmacist records.'
    ]);
}
