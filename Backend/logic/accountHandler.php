<?php
session_start();
header('Content-Type: application/json');

require_once('../config/dbaccess.php');
require_once('../models/user.class.php');

$response = ['success' => false];
$action = $_POST['action'] ?? '';

$userModel = new User();

// User muss eingeloggt sein!
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Nicht eingeloggt.']);
    exit;
}

$username = $_SESSION['username'];

switch ($action) {
    case 'getUserData':
        $user = $userModel->getByEmailOrUsername($username);
        if ($user) {
            $zahlung = $user[0]['zahlungsinfo'];
            $masked = $zahlung ? '**** **** **** ' . substr($zahlung, -4) : 'Keine Zahlungsinfo hinterlegt';
            $response = [
                'success' => true,
                'data' => [
                    'anrede' => $user[0]['anrede'],
                    'vorname' => $user[0]['vorname'],
                    'nachname' => $user[0]['nachname'],
                    'adresse' => $user[0]['adresse'],
                    'plz' => $user[0]['plz'],
                    'ort' => $user[0]['ort'],
                    'zahlungsinfo' => $masked
                ]
            ];
        } else {
            $response['message'] = 'Benutzer nicht gefunden.';
        }
        break;

    case 'updateAddress':
    case 'updatePayment':
        $password = $_POST['password'] ?? '';
        $newData = $_POST['newData'] ?? [];

        if (empty($password) || empty($newData)) {
            $response['message'] = 'Fehlende Daten.';
            break;
        }

        $user = $userModel->getByEmailOrUsername($username);

        if (!$user || !password_verify($password, $user[0]['password_hash'])) {
            $response['message'] = 'Falsches Passwort.';
            break;
        }

        $success = $userModel->updateUserData($username, $newData);

        if ($success) {
            $response['success'] = true;
            $response['message'] = 'Daten erfolgreich aktualisiert.';
        } else {
            $response['message'] = 'Fehler beim Speichern.';
        }
        break;

    case 'changePassword':
        $currentPassword = $_POST['currentPassword'] ?? '';
        $newPassword = $_POST['newPassword'] ?? '';

        if (empty($currentPassword) || empty($newPassword)) {
            $response['message'] = 'Fehlende Passwörter.';
            break;
        }

        $user = $userModel->getByEmailOrUsername($username);

        if (!$user || !password_verify($currentPassword, $user[0]['password_hash'])) {
            $response['message'] = 'Aktuelles Passwort ist falsch.';
            break;
        }

        // Passwort validieren (Mindestlänge prüfen, optional mehr Regeln)
        if (strlen($newPassword) < 8) {
            $response['message'] = 'Neues Passwort muss mindestens 8 Zeichen lang sein.';
            break;
        }

        // Passwort aktualisieren
        $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateSuccess = $userModel->updateUserData($username, ['password_hash' => $hashedNewPassword]);

        if ($updateSuccess) {
            $response['success'] = true;
            $response['message'] = 'Passwort erfolgreich geändert.';
        } else {
            $response['message'] = 'Fehler beim Passwort-Update.';
        }
        break;

    default:
        $response['message'] = 'Ungültige Aktion.';
}

echo json_encode($response);
?>
