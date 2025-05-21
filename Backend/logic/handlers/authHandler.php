<?php
if (session_status() === PHP_SESSION_NONE)
    session_start();

require_once(__DIR__ . '/../../config/dbaccess.php');
require_once(__DIR__ . '/../../models/user.class.php');
require_once(__DIR__ . '/../utils/auth.php');

$userModel = new User();
$action = $_POST['action'] ?? '';
$response = ['success' => false];


// ==== LOGIN =========================================================
if ($action === 'login') {
    $loginCredentials = $_POST['loginCredentials'] ?? '';
    $password = $_POST['password'] ?? '';
    $stayLoggedIn = $_POST['stayLoggedIn'] ?? false;

    if (empty($loginCredentials) || empty($password)) {
        $response['message'] = "Bitte füllen Sie alle Felder aus.";
    } else {
        $result = $userModel->getByEmailOrUsername($loginCredentials);

        if (count($result) === 1) { // wenn genau 1 User mit den Daten gefunden wurde
            if ($result[0]['is_active'] == 'inactive') {
                $response['message'] = "Dein Account ist deaktiviert. Bitte kontaktiere den Support.";
            } elseif (password_verify($password, $result[0]['password_hash'])) {
                $_SESSION["username"] = $result[0]['username'];
                $_SESSION["role"] = $result[0]['role'];

                // Cookie "stayLoggedIn" wird für 30 Tage gesetzt (60s * 60 * 24 = 86400)
                if ($stayLoggedIn === "true" || $stayLoggedIn === true) {
                    setcookie("stayLoggedIn", $loginCredentials, [
                        'expires' => time() + (86400 * 30),
                        'path' => '/'
                    ]);
                }

                $response['success'] = true;
                $response['message'] = "Eingeloggt! Hallo " . $result[0]['username'];
            } else {
                $response['message'] = "Login fehlgeschlagen – Benutzer oder Passwort ungültig.";
            }
        } else {
            $response['message'] = "Login fehlgeschlagen – Benutzer oder Passwort ungültig.";
        }
    }
}


// ==== REGISTER ======================================================
elseif ($action === 'register') {
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';

    if (!empty($email) && !empty($username)) {
        if ($userModel->emailExists($email)) {
            $response['message'] = "E-Mail ist bereits vergeben.";
        } elseif ($userModel->usernameExists($username)) {
            $response['message'] = "Benutzername ist bereits vergeben.";
        } elseif ($userModel->register($_POST)) {
            $response['success'] = true;
            $response['message'] = "Benutzer erfolgreich registriert.";
        } else {
            $response['message'] = "Registrierung fehlgeschlagen.";
        }
    } else {
        $response['message'] = "E-Mail oder Benutzername fehlt.";
    }
}


// ==== AUTO-LOGIN ====================================================
elseif ($action === 'autoLogin') {
    $loginCredentials = $_POST['loginCredentials'] ?? '';

    if (empty($loginCredentials)) {
        $response['message'] = "Kein Benutzer gespeichert.";
    } else {
        $result = $userModel->getByEmailOrUsername($loginCredentials);

        if (count($result) === 1) {
            $_SESSION["username"] = $result[0]['username'];
            $_SESSION["role"] = $result[0]['role'];

            // setzt Cookielebenszeit wieder auf 30 Tage
            setcookie("stayLoggedIn", $loginCredentials, [
                'expires' => time() + (86400 * 30),
                'path' => '/'
            ]);

            $response['success'] = true;
            $response['username'] = $result[0]['username'];
            $response['message'] = "Automatisch eingeloggt.";
        } else {
            $response['message'] = "Benutzer nicht gefunden.";
        }
    }
}

echo json_encode($response);
exit;
