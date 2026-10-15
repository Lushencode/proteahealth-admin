<?php

header("Content-Type: application/json");

require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method"
    ]);
    exit;
}

$id = $_POST["id"] ?? "";
$role = strtolower($_POST["role"] ?? "");

if (empty($id) || empty($role)) {
    echo json_encode([
        "success" => false,
        "message" => "ID and role are required"
    ]);
    exit;
}

try {

    switch ($role) {

        // -------------------------
        // PATIENT
        // -------------------------
        case "patient":

            $name = trim($_POST["name"] ?? "");
            $surname = trim($_POST["surname"] ?? "");
            $age = $_POST["age"] ?? "";
            $email = trim($_POST["email"] ?? "");
            $phone = trim($_POST["phone"] ?? "");
            $gender = trim($_POST["gender"] ?? "");
            $homeAddress = trim($_POST["home_address"] ?? "");
            $emergencyName = trim($_POST["emergency_contact_name"] ?? "");
            $emergencyNumber = trim($_POST["emergency_contact_number"] ?? "");
            $emergencyRelationship = trim($_POST["emergency_contact_relationship"] ?? "");
            $allergies = trim($_POST["allergies"] ?? "");
            $medicalConditions = trim($_POST["medical_conditions"] ?? "");

            if (empty($name) || empty($surname) || empty($email)) {
                echo json_encode([
                    "success" => false,
                    "message" => "Name, surname and email are required"
                ]);
                exit;
            }

            $stmt = $conn->prepare("
                UPDATE Patient
                SET
                    name = ?,
                    surname = ?,
                    age = ?,
                    email = ?,
                    phone = ?,
                    gender = ?,
                    home_address = ?,
                    emergency_contact_name = ?,
                    emergency_contact_number = ?,
                    emergency_contact_relationship = ?,
                    allergies = ?,
                    medical_conditions = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $name,
                $surname,
                $age,
                $email,
                $phone,
                $gender,
                $homeAddress,
                $emergencyName,
                $emergencyNumber,
                $emergencyRelationship,
                $allergies,
                $medicalConditions,
                $id
            ]);

            break;


        // -------------------------
        // DOCTOR
        // -------------------------
        case "doctor":

            $name = trim($_POST["name"] ?? "");
            $surname = trim($_POST["surname"] ?? "");
            $email = trim($_POST["email"] ?? "");
            $specialization = trim($_POST["specialization"] ?? "");
            $location = trim($_POST["location"] ?? "");
            $clinicName = trim($_POST["clinic_name"] ?? "");
            $yearsProfessional = $_POST["years_in_professional"] ?? "";
            $consultationPrice = $_POST["consultation_price"] ?? "";

            if (empty($name) || empty($surname) || empty($email)) {
                echo json_encode([
                    "success" => false,
                    "message" => "Name, surname and email are required"
                ]);
                exit;
            }

            $stmt = $conn->prepare("
                UPDATE Doctor
                SET
                    name = ?,
                    surname = ?,
                    email = ?,
                    specialization = ?,
                    location = ?,
                    clinic_name = ?,
                    years_in_professional = ?,
                    consultation_price = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $name,
                $surname,
                $email,
                $specialization,
                $location,
                $clinicName,
                $yearsProfessional,
                $consultationPrice,
                $id
            ]);

            break;


        // -------------------------
        // PHARMACIST
        // -------------------------
        
case "pharmacist":

    $name = trim($_POST["name"] ?? "");
    $surname = trim($_POST["surname"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $pharmacyLocation = trim($_POST["pharmacy_location"] ?? "");
    $workExperience = $_POST["work_experience_years"] ?? "";

    if ($name === "" || $surname === "" || $email === "") {
        echo json_encode([
            "success" => false,
            "message" => "Name, surname and email are required"
        ]);
        exit;
    }

    // Only update delivery settings when both are submitted.
    $updateDelivery =
        isset($_POST["offers_delivery"]) &&
        isset($_POST["delivery_fee"]);

    if ($updateDelivery) {

        $offersDelivery = $_POST["offers_delivery"];
        $deliveryFee = trim($_POST["delivery_fee"]);

        if (!in_array($offersDelivery, ["0", "1"], true)) {
            echo json_encode([
                "success" => false,
                "message" => "Invalid delivery availability"
            ]);
            exit;
        }

        if ($offersDelivery === "0") {
            $deliveryFee = "0.00";
        } elseif (
            !preg_match(
                '/^\d{1,8}(\.\d{1,2})?$/',
                $deliveryFee
            )
        ) {
            echo json_encode([
                "success" => false,
                "message" => "Enter a valid delivery fee"
            ]);
            exit;
        }
    }

    $conn->beginTransaction();

    // Find the pharmacy belonging to this pharmacist.
    $pharmacyStmt = $conn->prepare("
        SELECT pharmacy_id
        FROM Pharmacist
        WHERE id = ?
        FOR UPDATE
    ");

    $pharmacyStmt->execute([$id]);

    $pharmacyId = $pharmacyStmt->fetchColumn();

    if (!$pharmacyId) {
        $conn->rollBack();

        echo json_encode([
            "success" => false,
            "message" => "No pharmacy linked to this pharmacist"
        ]);
        exit;
    }

    // Update pharmacist information.
    $stmt = $conn->prepare("
        UPDATE Pharmacist
        SET
            name = ?,
            surname = ?,
            email = ?,
            phone = ?,
            pharmacy_location = ?,
            work_experience_years = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $name,
        $surname,
        $email,
        $phone,
        $pharmacyLocation,
        $workExperience,
        $id
    ]);

    // Update delivery settings in Pharmacies.
    if ($updateDelivery) {

        $deliveryStmt = $conn->prepare("
            UPDATE Pharmacies
            SET
                offers_delivery = ?,
                delivery_fee = ?
            WHERE id = ?
        ");

        $deliveryStmt->execute([
            $offersDelivery,
            $deliveryFee,
            $pharmacyId
        ]);
    }

    $conn->commit();

    break;



        // -------------------------
        // PHARMACY
        // -------------------------
        case "pharmacy":

            $name = trim($_POST["name"] ?? "");
            $email = trim($_POST["email"] ?? "");
            $location = trim($_POST["location"] ?? "");
            $openHour = trim($_POST["open_hour"] ?? "");
            $closeHours = trim($_POST["close_hours"] ?? "");
            $details = trim($_POST["details"] ?? "");

            if (empty($name) || empty($email)) {
                echo json_encode([
                    "success" => false,
                    "message" => "Name and email are required"
                ]);
                exit;
            }

            $stmt = $conn->prepare("
                UPDATE Pharmacies
                SET
                    name = ?,
                    email = ?,
                    location = ?,
                    open_hour = ?,
                    close_hours = ?,
                    details = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $name,
                $email,
                $location,
                $openHour,
                $closeHours,
                $details,
                $id
            ]);

            break;


        default:

            echo json_encode([
                "success" => false,
                "message" => "Invalid role"
            ]);
            exit;
    }


    if ($stmt->rowCount() > 0) {

        echo json_encode([
            "success" => true,
            "message" => "Profile updated successfully"
        ]);

    } else {

        // rowCount can also be 0 when the submitted values
        // are identical to the existing values.
        echo json_encode([
            "success" => true,
            "message" => "Profile saved successfully"
        ]);
    }

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
    $conn->rollBack();
}

    // Duplicate email
    if ($e->getCode() === "23000") {

        echo json_encode([
            "success" => false,
            "message" => "That email address is already in use"
        ]);

    } else {

        echo json_encode([
            "success" => false,
            "message" => "Database error"
        ]);
    }
}
?>