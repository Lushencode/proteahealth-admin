<?php

header("Content-Type: application/json");

require_once "db.php";

try {

    $stmt = $conn->prepare("
        SELECT
            id,
            name,
            surname,
            specialization,
            clinic_name,
            location,
            consultation_price
        FROM Doctor
        WHERE verification = 'verified'
        ORDER BY surname, name
    ");

    $stmt->execute();

    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "message" => "Doctors loaded successfully",
        "doctors" => $doctors
    ]);

} catch (PDOException $e) {

    echo json_encode([
        "success" => false,
        "message" => "Unable to load doctors",
        "doctors" => []
    ]);
}
?>