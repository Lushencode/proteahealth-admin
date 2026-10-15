
<?php

header('Content-Type: application/json; charset=utf-8');

session_name('proteahealth_admin');
session_start();

// Require administrator login
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Please log in.'
    ]);
    exit;
}

// Allow GET requests only
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Only GET requests are allowed.'
    ]);
    exit;
}

// Validate pharmacy ID
$pharmacyId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (!$pharmacyId) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'A valid pharmacy ID is required.'
    ]);
    exit;
}

try {
    require_once __DIR__ . '/../db.php';

    // Retrieve pharmacy details
    $pharmacyStmt = $conn->prepare("
        SELECT
            id,
            name,
            location,
            email,
            open_hour,
            close_hours,
            details,
            verification
        FROM Pharmacies
        WHERE id = :id
        LIMIT 1
    ");

    $pharmacyStmt->execute([
        ':id' => $pharmacyId
    ]);

    $pharmacy = $pharmacyStmt->fetch(PDO::FETCH_ASSOC);

    if (!$pharmacy) {
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Pharmacy not found.'
        ]);
        exit;
    }

    // Retrieve pharmacists linked to this pharmacy
    $pharmacistStmt = $conn->prepare("
        SELECT
            id,
            name,
            surname,
            email,
            verification
        FROM Pharmacist
        WHERE pharmacy_id = :pharmacy_id
        ORDER BY id DESC
    ");

    $pharmacistStmt->execute([
        ':pharmacy_id' => $pharmacyId
    ]);

    $pharmacists = $pharmacistStmt->fetchAll(
        PDO::FETCH_ASSOC
    );

    echo json_encode([
        'success' => true,
        'pharmacy' => $pharmacy,
        'pharmacists' => $pharmacists,
        'pharmacist_count' => count($pharmacists)
    ]);

} catch (Throwable $error) {
    error_log(
        'Pharmacy details error: ' . $error->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to retrieve pharmacy details.'
    ]);
}
