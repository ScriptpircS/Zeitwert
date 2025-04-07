<?php
include '../config/dbaccess.php'; // Datenbankverbindung

// JSON-Daten empfangen und dekodieren
$inputData = json_decode(file_get_contents('php://input'), true);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $loginCredentials = $inputData['loginCredentials'];
    $password = $inputData['password'];

    if (empty($loginCredentials) || empty($password)) {
        echo json_encode(["success" => false, "message" => "Bitte füllen Sie alle Felder aus."]);
        exit();
    }

    // Verbindung zur Datenbank
    $db = dbaccess::getInstance();

    // SQL-Abfrage vorbereiten
    $sql = "SELECT benutzername, password FROM users WHERE benutzername = ? OR email = ?";
    $result = $db->select($sql, [$loginCredentials, $loginCredentials]);

    if (count($result) === 1) {
        $user = $result[0];

        // Passwort prüfen
        if (password_verify($password, $user['password'])) {
            session_start();
            $_SESSION["Benutzername"] = $user['benutzername'];
            echo json_encode(["success" => true, "message" => "Eingeloggt! Hallo " . $user['benutzername']]);
        } else {
            echo json_encode(["success" => false, "message" => "Falsches Passwort!"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Benutzer nicht gefunden!"]);
    }
}
?>
