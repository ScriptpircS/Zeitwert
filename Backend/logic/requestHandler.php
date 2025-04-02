<?php
include 'config/dbaccess.php'; // Include die dbaccess-Klasse

/* JSON-Daten empfangen */
$inputData = json_decode(file_get_contents('php://input'), true);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Die Eingabedaten aus dem JSON holen
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

    // Validierung der Eingabedaten
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
    $password_hash = password_hash($password, PASSWORD_DEFAULT); /*Passwordhashing*/

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
        ':zahlungsinfo' => $zahlungsinfo
    ];

    // Führe das Prepared Statement aus
    if ($db->execute($sql, $params)) {
        echo json_encode(["success" => true, "message" => "Benutzer erfolgreich registriert."]);
    } else {
        echo json_encode(["success" => false, "message" => "Fehler bei der Registrierung."]);
    }
}
?>
