
<?php

header('Content-Type: application/json; charset=utf-8');

session_name('proteahealth_admin');
session_start();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Please log in as an administrator.'
    ]);

    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Only GET requests are allowed.'
    ]);

    exit;
}

try {
    require_once __DIR__ . '/../db.php';

    $sql = "
        SELECT
            p.id,
            p.name,
            p.location,
            p.verification,
            (
                SELECT COUNT(*)
                FROM Pharmacist AS ph
                WHERE ph.pharmacy_id = p.id
            ) AS pharmacist_count
        FROM Pharmacies AS p
        ORDER BY p.id DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $pharmacies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'pharmacies' => $pharmacies,
        'total' => count($pharmacies)
    ]);

} catch (Throwable $error) {
    error_log('Get pharmacies error: ' . $error->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to retrieve pharmacy records.'
    ]);
}
