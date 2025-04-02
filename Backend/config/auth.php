<?php
session_start();
require '../../Backend/config/dbacess.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $uname_login = trim($_POST["uname_login"]);
    $pw_login = $_POST["pw_login"];
    $errors = [];

    if (empty($uname_login) || empty($pw_login)) {
        $errors[] = "Benutzername/E-Mail und Passwort sind erforderlich.";
    } else {
        $db = dbaccess::getInstance();
        $sql = "SELECT id, username, password FROM users WHERE username = ? OR email = ?";
        $result = $db->select($sql, [$uname_login, $uname_login]);

        if (count($result) === 1) {
            $user = $result[0];

            // ⚠️ Hier direkte Passwortüberprüfung ohne Hash
            if ($pw_login === $user['password']) {
                $_SESSION["Benutzername"] = $user['username'];
                $_SESSION["UserID"] = $user['id'];
                header("Location: ../../Frontend/sites/login.php"); // Erfolgreich eingeloggt
                exit();
            } else {
                $errors[] = "Falsches Passwort!";
            }
        } else {
            $errors[] = "Benutzer nicht gefunden!";
        }
    }

    $_SESSION["login_errors"] = $errors;
    header("Location: ../../Frontend/sites/login.php"); // Zurück zur Login-Seite mit Fehlermeldung
    exit();
}

// Version für gehashte Passwörter. Da zu Testzwecken nur eine provisorische DB und kein Registrierungs
// Formular existiert. Oberer Teil kann nach Sprint 1 angepasst und geändert werden.

/* 
session_start();
require '../../Backend/config/dbacess.php'; // Korrigierter Name

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $uname_login = trim($_POST["uname_login"]);
    $pw_login = $_POST["pw_login"];
    $errors = [];

    if (empty($uname_login) || empty($pw_login)) {
        $errors[] = "Benutzername/E-Mail und Passwort sind erforderlich.";
    } else {
        $db = dbaccess::getInstance();  // Korrigierter Name
        $sql = "SELECT id, username, password FROM users WHERE username = ? OR email = ?";
        $result = $db->select($sql, [$uname_login, $uname_login]);

        if (count($result) === 1) {
            $user = $result[0];

            // Prüfen, ob Passwort als Hash gespeichert ist
            if (password_verify($pw_login, $user['password'])) {
                $_SESSION["Benutzername"] = $user['username'];
                $_SESSION["UserID"] = $user['id'];
                header("Location: ../../index.html"); // Erfolgreiche Anmeldung
                exit();
            } else {
                $errors[] = "Falsches Passwort!";
            }
        } else {
            $errors[] = "Benutzer nicht gefunden!";
        }
    }

    $_SESSION["login_errors"] = $errors;
    header("Location: login.php"); // Zurück zur Login-Seite mit Fehlermeldung
    exit();
}*/
?>
