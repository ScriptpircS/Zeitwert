<?php
if (session_status() === PHP_SESSION_NONE)
    session_start();

require_once(__DIR__ . '/../../config/dbaccess.php');
require_once(__DIR__ . '/../../models/user.class.php');
require_once(__DIR__ . '/../utils/auth.php');

requireLogin();

$response = ['success' => false];
$action = $_POST['action'] ?? '';
$userModel = new User();
$username = $_SESSION['username'];
$db = dbaccess::getInstance();

// Benutzerdaten laden
if ($action === 'getAccountData') {
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
}

// Adresse oder Zahlung speichern
elseif (in_array($action, ['updateAddress', 'updatePayment'])) {
    $password = $_POST['password'] ?? '';
    $newData = $_POST['newData'] ?? [];

    if (empty($password) || empty($newData)) {
        $response['message'] = 'Fehlende Daten.';
    } else {
        $user = $userModel->getByEmailOrUsername($username);

        if (!$user || !password_verify($password, $user[0]['password_hash'])) {
            $response['message'] = 'Falsches Passwort.';
        } else {
            $success = $userModel->updateUserData($username, $newData);

            if ($success) {
                $response['success'] = true;
                $response['message'] = 'Daten erfolgreich aktualisiert.';
            } else {
                $response['message'] = 'Fehler beim Speichern.';
            }
        }
    }
}

// Passwort ändern
elseif ($action === 'changePassword') {
    $currentPassword = $_POST['currentPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';

    if (empty($currentPassword) || empty($newPassword)) {
        $response['message'] = 'Fehlende Passwörter.';
    } else {
        $user = $userModel->getByEmailOrUsername($username);

        if (!$user || !password_verify($currentPassword, $user[0]['password_hash'])) {
            $response['message'] = 'Aktuelles Passwort ist falsch.';
        } elseif (strlen($newPassword) < 8) {
            $response['message'] = 'Neues Passwort muss mindestens 8 Zeichen lang sein.';
        } else {
            $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateSuccess = $userModel->updateUserData($username, ['password_hash' => $hashedNewPassword]);

            if ($updateSuccess) {
                $response['success'] = true;
                $response['message'] = 'Passwort erfolgreich geändert.';
            } else {
                $response['message'] = 'Fehler beim Passwort-Update.';
            }
        }
    }
}

// Zahlungsmethoden anzeigen
elseif ($action === 'loadPaymentMethods') {
    requireLogin();

    try {
        $userResult = $userModel->getByEmailOrUsername($username);

        if (count($userResult) !== 1) {
            throw new Exception("Benutzerdaten konnten nicht geladen werden.");
        }

        $user = $userResult[0];
        $userId = $user['id'];

        $sql = "SELECT id, type, details FROM payment_methods WHERE user_id = ?";
        $data = $db->select($sql, [$userId]);

        $response['success'] = true;
        $response['data'] = $data;
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
}

// Zahlungsmethode laden
elseif ($action === 'getPaymentMethod') {
    requireLogin();

    $id = $_POST['paymentId'];

    try {
        $userResult = $userModel->getByEmailOrUsername($username);

        if (count($userResult) !== 1) {
            throw new Exception("Benutzerdaten konnten nicht geladen werden.");
        }

        $user = $userResult[0];
        $userId = $user['id'];

        $sql = "SELECT type, details FROM payment_methods WHERE user_id = ? AND id = ?";
        $data = $db->select($sql, [$userId, $id]);

        $response['success'] = true;
        $response['data'] = $data;
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
}

// Neue Zahlungsmethode speichern
elseif ($action === 'addPaymentMethod') {
    requireLogin();

    try {
        $username = $_SESSION['username'];
        $userResult = $userModel->getByEmailOrUsername($username);

        if (count($userResult) !== 1) {
            throw new Exception("Benutzer nicht gefunden.");
        }

        $user = $userResult[0];
        $userId = $user['id'];

        $type = trim($_POST['type'] ?? '');
        $details = trim($_POST['details'] ?? '');

        if (!$type || !$details) {
            throw new Exception("Bitte alle Felder ausfüllen.");
        }

        $sql = "INSERT INTO payment_methods (user_id, type, details) VALUES (?, ?, ?)";
        $db->execute($sql, [$userId, $type, $details]);

        $response['success'] = true;
    } catch (Exception $e) {
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }
}

// Zahlungsmethode bearbeiten
elseif ($action === 'updatePaymentMethod') {
    requireLogin();

    $username = $_SESSION['username'];
    $userResult = $userModel->getByEmailOrUsername($username);

    if (count($userResult) !== 1) {
        throw new Exception("Benutzer nicht gefunden.");
    }

    $user = $userResult[0];
    $userId = $user['id'];

    $id = $_POST['paymentId'];
    $type = $_POST['type'];
    $details = $_POST['details'];

    $sql = "UPDATE payment_methods SET type = ?, details = ? WHERE id = ? AND user_id = ?";
    $db->execute($sql, [$type, $details, $id, $userId]);

}

// Zahlungsart löschen
elseif ($action === 'deletePaymentMethod') {
    requireLogin();

    $id = $_POST['paymentId'];

    try {
        $username = $_SESSION['username'];
        $userResult = $userModel->getByEmailOrUsername($username);

        if (count($userResult) !== 1) {
            throw new Exception("Benutzer nicht gefunden.");
        }

        $user = $userResult[0];
        $userId = $user['id'];

        $sql = "DELETE FROM payment_methods WHERE id = ? AND user_id = ?";
        $db->execute($sql, [$id, $userId]);

        $response['success'] = true;
    } catch (Exception $e) {
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }
}

// Fallback
else {
    $response['message'] = 'Ungültige Aktion.';
}

echo json_encode($response);
exit;
