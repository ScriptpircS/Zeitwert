<?php
include 'config/dbaccess.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$inputData = json_decode(file_get_contents('php://input'), true);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $anrede = $inputData['anrede'];
    $vorname = $inputData['vorname'];
    $nachname = $inputData['nachname'];
    $adresse = $inputData['adresse'];
    $plz = $inputData['plz'];
    $ort = $inputData['ort'];
    $email = $inputData['email'];
    $username = $inputData['username'];
    $password = $inputData['password'];
    $password_repeat = $inputData['password_repeat'];

    if ($password !== $password_repeat) {
        echo json_encode(["success" => false, "message" => "Die Passwörter stimmen nicht überein."]);
        exit();
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $db = dbaccess::getInstance();
    $sql = "INSERT INTO users (anrede, vorname, nachname, adresse, plz, ort, email, benutzername, password, zahlungsinfo)
            VALUES (:anrede, :vorname, :nachname, :adresse, :plz, :ort, :email, :benutzername, :password, :zahlungsinfo)";

    $params = [
        ':anrede' => $anrede,
        ':vorname' => $vorname,
        ':nachname' => $nachname,
        ':adresse' => $adresse,
        ':plz' => $plz,
        ':ort' => $ort,
        ':email' => $email,
        ':username' => $username,
        ':password' => $password_hash,

    ];

    // Execute the query and check if the insertion was successful
    if ($db->execute($sql, $params)) {
        echo json_encode(["success" => true, "message" => "Benutzer erfolgreich registriert."]);
    } else {
        // If the insertion failed, respond with an error message
        echo json_encode(["success" => false, "message" => "Fehler bei der Registrierung."]);
    }
}
?>
