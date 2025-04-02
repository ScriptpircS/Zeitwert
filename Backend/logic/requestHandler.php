<?php
include dbaccess.php; /* Datenbankverbindung */

/* JSON-Daten empfangen*/
$inputData = json_decode(file_get_contents('php://input'), true);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $anrede = $inputData['anrede'];
    $vorname = $inputData['vorname'];
    $nachname = $inputData['nachname'];
    $adresse = $inputData['adresse'];
    $plz = $inputData['plz'];
    $ort = $inputData['ort'];
    $email = $inputData['email'];
    $benutzername = $inputData['benutzername'];
    $password = $inputData['password'];
    $password_repeat = $inputData['password_repeat'];
    $zahlungsinfo = $inputData['zahlungsinfo'];

    if (empty($vorname) || empty($nachname) || empty($adresse) || empty($email)) {
        echo json_encode(["success" => false, "message" => "Bitte füllen Sie alle Pflichtfelder aus."]);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["success" => false, "message" => "Ungültige E-Mail-Adresse."]);
        exit();
    }

    if ($password !== $password_repeat) {
        echo json_encode(["success" => false, "message" => "Die Passwörter stimmen nicht überein."]);
        exit();
    }
    $password_hash = password_hash($password, PASSWORD_DEFAULT); /*Password Hashing*/

    /* SQL-Query zum Einfügen des neuen Users */
    $sql = "INSERT INTO users (anrede, vorname, nachname, adresse, plz, ort, email, benutzername, password, zahlungsinfo)
            VALUES ('$anrede', '$vorname', '$nachname', '$adresse', '$plz', '$ort', '$email', '$benutzername', '$password_hash', '$zahlungsinfo')";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(["success" => true, "message" => "Benutzer erfolgreich registriert."]);
    } else {
        echo json_encode(["success" => false, "message" => "Fehler: " . $conn->error]);
    }

    $conn->close();
}
?>