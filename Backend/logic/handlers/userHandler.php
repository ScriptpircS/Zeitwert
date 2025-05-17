<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once(__DIR__ . '/../../config/dbaccess.php');
require_once(__DIR__ . '/../../models/user.class.php');
require_once(__DIR__ . '/../utils/auth.php');

$userModel = new User();
$action = $_POST['action'] ?? '';
$response = ['success' => false];


// ===== getUserData (nur für eingeloggte Kunden) =====
if ($action === 'getUserData') {
    requireLogin();

    $username = $_SESSION['username'];
    $userData = $userModel->getByEmailOrUsername($username);

    if (count($userData) === 1) {
        $response['success'] = true;
        $response['user'] = [
            'anrede' => $userData[0]['anrede'],
            'vorname' => $userData[0]['vorname'],
            'nachname' => $userData[0]['nachname'],
            'adresse' => $userData[0]['adresse'],
            'plz' => $userData[0]['plz'],
            'ort' => $userData[0]['ort'],
            'zahlungsinfo' => $userData[0]['zahlungsinfo'],
        ];
    } else {
        $response['message'] = "Benutzerdaten konnten nicht gefunden werden.";
    }
}


// ===== listCustomers (nur Admin) =====
elseif ($action === 'listCustomers') {
    requireAdmin();

    $customers = $userModel->getAllCustomers();
    $response['success'] = true;
    $response['customers'] = $customers;
}


// ===== toggleCustomer (nur Admin) =====
elseif ($action === 'toggleCustomer') {
    requireAdmin();

    $userId = $_POST['id'] ?? null;
    if ($userId) {
        $newStatus = $userModel->toggleCustomerStatus($userId);
        if ($newStatus !== null) {
            $response['success'] = true;
            $response['message'] = $newStatus ? "Kunde aktiviert." : "Kunde deaktiviert.";
        } else {
            $response['message'] = "Kunde nicht gefunden.";
        }
    } else {
        $response['message'] = "Kunden-ID fehlt.";
    }
}

echo json_encode($response);
exit;
