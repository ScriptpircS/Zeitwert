<?php
include('../config/dbaccess.php');

header('Content-Type: application/json');
session_start();

$response = ['success' => false];
$db = dbaccess::getInstance();

// Prüfen ob eine Aktion übergeben wurde
$action = $_POST['action'] ?? '';

if ($action === 'login') {
    $loginCredentials = $_POST['loginCredentials'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($loginCredentials) || empty($password)) {
        $response['message'] = "Bitte füllen Sie alle Felder aus.";
    } else {
        $sql = "SELECT benutzername, password FROM users WHERE benutzername = ? OR email = ?";
        $result = $db->select($sql, [$loginCredentials, $loginCredentials]);

        if (count($result) === 1) {
            $user = $result[0];

            if ($password === $user['password']) {
                $_SESSION["Benutzername"] = $user['benutzername'];
                $response['success'] = true;
                $response['message'] = "Eingeloggt! Hallo " . $user['benutzername'];
            } else {
                $response['message'] = "Falsches Passwort!";
            }
            /* Für Testzwecke ungehashtes Passwort. Unbedingt wieder austauschen sobald vereint!
            if (password_verify($password, $user['password'])) {
                $_SESSION["Benutzername"] = $user['benutzername'];
                $response['success'] = true;
                $response['message'] = "Eingeloggt! Hallo " . $user['benutzername'];
            } else {
                $response['message'] = "Falsches Passwort!";
            }*/
        } else {
            $response['message'] = "Benutzer nicht gefunden!";
        }
    }

} elseif ($action === 'register') {
    if (!empty($_POST['email'])) {
        $sql = "INSERT INTO users (
                    anrede, vorname, nachname, adresse, plz, ort, email, username, password
                ) VALUES (
                    :anrede, :vorname, :nachname, :adresse, :plz, :ort, :email, :username, :password_hash
                )";

        $params = [
            ':anrede' => $_POST['anrede'],
            ':vorname' => $_POST['vorname'],
            ':nachname' => $_POST['nachname'],
            ':adresse' => $_POST['adresse'],
            ':plz' => $_POST['plz'],
            ':ort' => $_POST['ort'],
            ':email' => $_POST['email'],
            ':username' => $_POST['username'],
            ':password_hash' => password_hash($_POST['password'], PASSWORD_DEFAULT)
        ];

        if ($db->execute($sql, $params)) {
            $response['success'] = true;
            $response['message'] = "Benutzer erfolgreich registriert.";
        } else {
            $response['message'] = "Fehler bei der Registrierung.";
        }
    } else {
        $response['message'] = "E-Mail fehlt.";
    }

} else {
    $response['message'] = "Ungültige Aktion.";
}

echo json_encode($response);
?>