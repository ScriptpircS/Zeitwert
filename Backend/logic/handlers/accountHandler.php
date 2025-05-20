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
        $response = [
            'success' => true,
            'data' => [
                'anrede' => $user[0]['anrede'],
                'vorname' => $user[0]['vorname'],
                'nachname' => $user[0]['nachname'],
                'adresse' => $user[0]['adresse'],
                'plz' => $user[0]['plz'],
                'ort' => $user[0]['ort']
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
        $user = $userModel->getByEmailOrUsername($username);
        $userId = $user[0]['id'];

        if (count($user) !== 1) {
            throw new Exception("Benutzerdaten konnten nicht geladen werden.");
        }

        $sql = "SELECT id, type, details FROM payment_methods WHERE user_id = ?";
        $rawdata = $db->select($sql, [$userId]);

        // Daten maskieren
        $maskedData = array_map(function ($entry) {
            $type = $entry['type'];
            $details = $entry['details'];

            switch ($type) {
                case 'Kreditkarte':
                    $masked = $details ? '**** **** **** ' . substr($details, -4) : '****';
                    break;
                case 'PayPal':
                    $masked = preg_replace('/^(.)(.*)(@.*)$/', '$1***$3', $details);
                    break;
                case 'Bankeinzug':
                    $masked = preg_replace('/^(.{2})(.*)(.{4})$/', '$1' . str_repeat('*', max(strlen($details) - 6, 0)) . '$3', $details);
                    break;
                default:
                    $masked = '***';
            }

            return [
                'id' => $entry['id'],
                'type' => $type,
                'details' => $masked
            ];
        }, $rawdata);
        
        $response['success'] = true;
        $response['data'] = $maskedData;
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
}

// Zahlungsmethode laden
elseif ($action === 'getPaymentMethod') {
    requireLogin();

    $id = $_POST['paymentId'];

    try {
        $username = $_SESSION['username'];
        $user = $userModel->getByEmailOrUsername($username);
        $userId = $user[0]['id'];
        $password = trim($_POST['password'] ?? '');

        if (count($user) !== 1) {
            throw new Exception("Benutzerdaten konnten nicht geladen werden.");
        }

        if (!$password) {
            throw new Exception("Bitte Passwort ausfüllen.");
        } elseif (!$user || !password_verify($password, $user[0]['password_hash'])) {
            $response['message'] = 'Falsches Passwort.';
        } else {
            $sql = "SELECT type, details FROM payment_methods WHERE user_id = ? AND id = ?";
            $data = $db->select($sql, [$userId, $id]);

            $response['success'] = true;
            $response['data'] = $data;
        }

    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
}

// Neue Zahlungsmethode speichern
elseif ($action === 'addPaymentMethod') {
    requireLogin();

    try {
        $username = $_SESSION['username'];
        $user = $userModel->getByEmailOrUsername($username);
        $userId = $user[0]['id'];

        if (count($user) !== 1) {
            throw new Exception("Benutzer nicht gefunden.");
        }

        $type = trim($_POST['type'] ?? '');
        $details = trim($_POST['details'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (!$type || !$details || !$password) {
            throw new Exception("Bitte alle Felder ausfüllen.");
        } elseif (!$user || !password_verify($password, $user[0]['password_hash'])) {
            $response['message'] = 'Falsches Passwort.';
        } else {
            $sql = "INSERT INTO payment_methods (user_id, type, details) VALUES (?, ?, ?)";
            $db->execute($sql, [$userId, $type, $details]);

            $response['success'] = true;
        }

    } catch (Exception $e) {
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }
}

// Zahlungsmethode bearbeiten
elseif ($action === 'updatePaymentMethod') {
    requireLogin();

    try {
        $username = $_SESSION['username'];
        $user = $userModel->getByEmailOrUsername($username);
        $userId = $user[0]['id'];

        if (count($user) !== 1) {
            throw new Exception("Benutzer nicht gefunden.");
        }

        $id = $_POST['paymentId'];
        $type = trim($_POST['type'] ?? '');
        $details = trim($_POST['details'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (!$type || !$details || !$password) {
            throw new Exception("Bitte alle Felder ausfüllen.");
        } elseif (!$user || !password_verify($password, $user[0]['password_hash'])) {
            $response['message'] = 'Falsches Passwort.';
        } else {
            $sql = "UPDATE payment_methods SET type = ?, details = ? WHERE id = ? AND user_id = ?";
            $db->execute($sql, [$type, $details, $id, $userId]);

            $response['success'] = true;
        }

    } catch (Exception $e) {
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }


}

// Zahlungsart löschen
elseif ($action === 'deletePaymentMethod') {
    requireLogin();

    $id = $_POST['paymentId'];

    try {
        $username = $_SESSION['username'];
        $user = $userModel->getByEmailOrUsername($username);
        $userId = $user[0]['id'];
        $password = trim($_POST['password'] ?? '');

        if (count($user) !== 1) {
            throw new Exception("Benutzer nicht gefunden.");
        } elseif (!$user || !password_verify($password, $user[0]['password_hash'])) {
            $response['message'] = 'Falsches Passwort.';
        } else {
            $sql = "DELETE FROM payment_methods WHERE id = ? AND user_id = ?";
            $db->execute($sql, [$id, $userId]);

            $response['success'] = true;
        }


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
