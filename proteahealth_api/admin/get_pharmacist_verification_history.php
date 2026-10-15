<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

session_name('proteahealth_admin');
session_start();

if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// Use the same database connection path as get_pharmacists.php.
require_once __DIR__ . '/../db.php';

try {
    $stmt = $conn->prepare("
        SELECT
            audit.id,
            audit.pharmacist_id,
            audit.admin_id,
            audit.previous_status,
            audit.new_status,
            audit.decision_reason,
            audit.credential_reference,
            audit.created_at,

            CONCAT(p.name, ' ', p.surname)
                AS pharmacist_name,

            p.registration_code,

            ph.name AS pharmacy_name,

            CONCAT(a.name, ' ', a.surname)
                AS admin_name

        FROM PharmacistVerificationAudit AS audit

        LEFT JOIN Pharmacist AS p
            ON audit.pharmacist_id = p.id

        LEFT JOIN Pharmacies AS ph
            ON p.pharmacy_id = ph.id

        LEFT JOIN Admins AS a
            ON audit.admin_id = a.id

        ORDER BY
            audit.created_at DESC,
            audit.id DESC

        LIMIT 100
    ");

    $stmt->execute();

    echo json_encode([
        'success' => true,
        'history' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);

} catch (Throwable $e) {
    error_log('Pharmacist audit history error: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to retrieve pharmacist verification history'
    ]);
}
