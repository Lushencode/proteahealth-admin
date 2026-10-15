
<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

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

// Validate patient ID
$patientId = filter_var(
    $_GET['id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($patientId === false || $patientId === null) {
    respond(400, [
        'success' => false,
        'message' => 'A valid patient ID is required.'
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
        p.home_address,

        p.emergency_contact_name,
        p.emergency_contact_number,
        p.emergency_contact_relationship,

        p.allergies,
        p.medical_conditions,

        p.risk_level,
        p.doc_id,
        p.created_at,

        d.name AS doctor_name,
        d.surname AS doctor_surname,
        d.specialization AS doctor_specialization,
        d.clinic_name AS doctor_clinic

    FROM Patient AS p

    LEFT JOIN Doctor AS d
        ON p.doc_id = d.id

    WHERE p.id = :patient_id

    LIMIT 1
";


    $stmt = $conn->prepare($sql);

    $stmt->execute([
        ':patient_id' => $patientId
    ]);

    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        respond(404, [
            'success' => false,
            'message' => 'Patient not found.'
        ]);
    }

    respond(200, [
        'success' => true,
        'patient' => $patient
    ]);

} catch (Throwable $error) {
    error_log(
        'Get patient details error: ' . $error->getMessage()
    );

    respond(500, [
        'success' => false,
        'message' => 'Unable to retrieve patient details.'
    ]);
}
