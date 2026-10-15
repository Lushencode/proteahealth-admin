
<?php

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Method not allowed"
    ]);

    exit;
}

require_once __DIR__ . "/db.php";

try {

    // Determine the server address used by Android.
    $isHttps = (
        !empty($_SERVER["HTTPS"])
        && strtolower($_SERVER["HTTPS"]) !== "off"
    );

    $scheme = $isHttps ? "https" : "http";

    $host = $_SERVER["HTTP_HOST"] ?? "localhost";

    // Support PHP running at the web root or in
    // a subdirectory such as /proteahealth_api.
    $scriptDirectory = str_replace(
        "\\",
        "/",
        dirname($_SERVER["SCRIPT_NAME"] ?? "/")
    );

    $scriptDirectory = rtrim($scriptDirectory, "/.");

    $baseUrl = $scheme . "://" . $host . $scriptDirectory;

    $sql = "
        SELECT
            er.resource_id,
            er.category_id,
            ec.name AS category,
            er.provider_id,
            CONCAT(d.name, ' ', d.surname) AS author,
            er.title,
            er.image,
            er.summary,
            er.content,
            er.health_tip,
            er.read_time,
            er.created_at,
            er.updated_at
        FROM educational_resource er
        INNER JOIN Education_Category ec
            ON er.category_id = ec.category_id
        INNER JOIN Doctor d
            ON er.provider_id = d.id
        WHERE er.status = 'published'
          AND er.is_reviewed = 1
        ORDER BY er.created_at DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $tips = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert stored image paths into complete URLs.
    foreach ($tips as &$tip) {

        $imagePath = trim($tip["image"] ?? "");

        if ($imagePath === "") {
            $tip["image"] = null;
            continue;
        }

        // Preserve complete URLs already stored in MySQL.
        if (
            preg_match(
                '~^https?://~i',
                $imagePath
            )
        ) {
            $tip["image"] = $imagePath;
            continue;
        }

        // Normalize relative paths.
        $imagePath = str_replace("\\", "/", $imagePath);
        $imagePath = ltrim($imagePath, "/");

        // Only expose images from our upload directory.
        if (
            !preg_match(
                '~^uploads/health_tips/[a-zA-Z0-9_-]+\.(jpg|jpeg|png|webp)$~i',
                $imagePath
            )
        ) {
            $tip["image"] = null;
            continue;
        }

        // Construct the complete image URL.
        $tip["image"] = $baseUrl . "/" . $imagePath;
    }

    unset($tip);

    echo json_encode([
        "success" => true,
        "count" => count($tips),
        "health_tips" => $tips
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    error_log(
        "Health tips API error: " . $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to load health tips."
    ]);
}

?>
