
<?php

header("Content-Type: application/json");

require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed"
    ]);
    exit;
}

$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";
$role = strtolower(trim($_POST["role"] ?? ""));

if ($email === "" || $password === "" || $role === "") {
    echo json_encode([
        "success" => false,
        "message" => "Email, password and role are required"
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid email address"
    ]);
    exit;
}

/*
 * Determine user table
 */
switch ($role) {
    case "patient":
        $table = "Patient";
        break;

    case "doctor":
        $table = "Doctor";
        break;

    case "pharmacist":
        $table = "Pharmacist";
        break;

    default:
        echo json_encode([
            "success" => false,
            "message" => "Invalid user role"
        ]);
        exit;
}

try {

    /*
     * Find user
     */
    $stmt = $conn->prepare(
        "SELECT * FROM $table WHERE email = ? LIMIT 1"
    );

    $stmt->execute([$email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (
        !$user ||
        !password_verify(
            $password,
            $user["password_hash"] ?? ""
        )
    ) {
        http_response_code(401);

        echo json_encode([
            "success" => false,
            "message" => "Invalid email or password"
        ]);
        exit;
    }

    /*
     * Restrict unverified pharmacists
     */
    if (
        $role === "pharmacist" &&
        $user["verification"] !== "verified"
    ) {
        http_response_code(403);

        echo json_encode([
            "success" => false,
            "message" => "Your pharmacist account has not been verified"
        ]);
        exit;
    }

    /*
     * Create secure access token
     */
    $token = bin2hex(random_bytes(32));

    /*
     * Store only token hash in database
     */
    $tokenHash = hash("sha256", $token);

    /*
     * Token expires after 24 hours
     */
    $expiresAt = gmdate(
        "Y-m-d H:i:s",
        time() + (24 * 60 * 60)
    );

    /*
     * Save token
     */
    $tokenStmt = $conn->prepare("
        INSERT INTO AuthTokens
            (user_id, role, token_hash, expires_at)
        VALUES
            (?, ?, ?, ?)
    ");

    $tokenStmt->execute([
        $user["id"],
        $role,
        $tokenHash,
        $expiresAt
    ]);

    /*
     * Remove sensitive fields
     */
    unset($user["password_hash"]);

    /*
     * Successful login
     */
    echo json_encode([
        "success" => true,
        "message" => "Login successful",
        "role" => $role,
        "user" => $user,
        "access_token" => $token,
        "expires_at" => $expiresAt
    ]);

} catch (Throwable $e) {

    error_log("Login error: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "An internal server error occurred"
    ]);
}

?>
