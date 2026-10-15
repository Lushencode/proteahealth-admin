
<?php

header("Content-Type: application/json");

require_once "db.php";

$medicationName = trim($_GET["medication_name"] ?? "");

try {

    // Retrieve all active medications that are in stock.
    $sql = "
        SELECT
            pm.id AS medication_id,
            pm.pharmacy_id,

            p.name AS pharmacy_name,
            p.location AS pharmacy_location,
            p.open_hour,
            p.close_hours,
            p.offers_delivery,
            p.delivery_fee,

            pm.medication_name,
            pm.price,
            pm.availability

        FROM Pharmacy_Medication pm

        INNER JOIN Pharmacies p
            ON p.id = pm.pharmacy_id

        WHERE pm.is_active = 1
          AND pm.availability = 'available'
          AND pm.stock_quantity > 0
    ";

    $params = [];

    // Only filter by name if the patient searches.
    if ($medicationName !== "") {

        $sql .= "
            AND pm.medication_name LIKE :medication_name
        ";

        $params[":medication_name"] =
            "%" . $medicationName . "%";
    }

    // Same medications together, cheapest first.
    $sql .= "
        ORDER BY
            pm.medication_name ASC,
            pm.price ASC,
            p.name ASC
    ";

    $stmt = $conn->prepare($sql);

    $stmt->execute($params);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "message" => "Medication prices retrieved successfully",
        "pharmacies" => $results
    ]);

} catch (PDOException $e) {

    error_log(
        "Compare prices SQL error: " . $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to retrieve medication prices",
        "pharmacies" => []
    ]);
}

?>
