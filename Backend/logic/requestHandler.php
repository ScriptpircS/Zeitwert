<?php
// ==== INITIALISIERUNG & EINBINDUNGEN =====================================

header('Content-Type: application/json');
session_start();

require_once('../config/dbaccess.php');
require_once('../models/product.class.php');
require_once('../models/user.class.php');

$response = ['success' => false]; // Standardantwort
$action = $_POST['action'] ?? '';

$userModel = new User();
$productModel = new Product();

// ==== LOGIN =============================================================

if ($action === 'login') {
    $loginCredentials = $_POST['loginCredentials'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($loginCredentials) || empty($password)) {
        $response['message'] = "Bitte füllen Sie alle Felder aus.";
    } else {
        $result = $userModel->getByEmailOrUsername($loginCredentials);

        if (count($result) === 1 && password_verify($password, $result[0]['password_hash'])) {
            $_SESSION["Benutzername"] = $result[0]['username'];
            $response['success'] = true;
            $response['message'] = "Eingeloggt! Hallo " . $result[0]['username'];
        } else {
            $response['message'] = "Login fehlgeschlagen – Benutzer oder Passwort ungültig.";
        }
    }

// ==== REGISTRIERUNG ====================================================

} elseif ($action === 'register') {
    if (!empty($_POST['email']) && !empty($_POST['username'])) {
        try {
            if ($userModel->emailExists($_POST['email'])) {
                $response['message'] = "E-Mail ist bereits vergeben.";
            } elseif ($userModel->usernameExists($_POST['username'])) {
                $response['message'] = "Benutzername ist bereits vergeben.";
            } elseif ($userModel->register($_POST)) {
                $response['success'] = true;
                $response['message'] = "Benutzer erfolgreich registriert.";
            } else {
                $response['message'] = "Registrierung fehlgeschlagen.";
            }
        } catch (PDOException $e) {
            $response['message'] = "Datenbankfehler: " . $e->getMessage();
        }
    } else {
        $response['message'] = "E-Mail oder Benutzername fehlt.";
    }

// ==== PRODUKTÜBERSICHT LADEN ============================================

} elseif ($action === 'getProducts') {
    $products = $productModel->getAllProducts();

    $response['success'] = true;
    $response['products'] = $products;

// ==== DEFAULT: UNBEKANNTE AKTION ========================================

} else {
    $response['message'] = "Ungültige Aktion.";
}

// ==== JSON-ANTWORT ======================================================

echo json_encode($response);
?>
