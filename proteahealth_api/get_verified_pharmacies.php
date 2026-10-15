
<?php

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("X-Content-Type-Options: nosniff");

require_once __DIR__ . "/db.php";

function respond(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Only GET requests allowed
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    header("Allow: GET");

    respond(405, [
        "success" => false,
        "message" => "Only GET requests are allowed"
    ]);
}

try {

    // Only return verified pharmacies
    $stmt = $conn->prepare("
        SELECT
            id,
            name,
            location,
            open_hour,
            close_hours,
            offers_delivery,
            delivery_fee
        FROM Pharmacies
        WHERE verification = 'verified'
        ORDER BY name ASC, id ASC
    ");

    $stmt->execute();

    $pharmacies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the response for Android
    $result = [];

    foreach ($pharmacies as $pharmacy) {

        $result[] = [
            "id" => (int) $pharmacy["id"],
            "name" => $pharmacy["name"],
            "location" => $pharmacy["location"],
            "open_hour" => $pharmacy["open_hour"],
            "close_hours" => $pharmacy["close_hours"],
            "offers_delivery" =>
                (bool) $pharmacy["offers_delivery"],
            "delivery_fee" =>
                $pharmacy["delivery_fee"] !== null
                    ? (float) $pharmacy["delivery_fee"]
                    : 0.00
        ];
    }

    respond(200, [
        "success" => true,
        "count" => count($result),
        "pharmacies" => $result
    ]);

} catch (Throwable $e) {

    error_log(
        "Get verified pharmacies error: " .
        $e->getMessage()
    );

    respond(500, [
        "success" => false,
        "message" => "Unable to retrieve pharmacies"
    ]);
}
