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
        ];
    } else {
        $response['message'] = "Benutzerdaten konnten nicht gefunden werden.";
    }
}


// ===== listCustomers (Admin) =====
elseif ($action === 'listCustomers') {
    requireAdmin();

    $customers = $userModel->getAllCustomers();
    $response['success'] = true;
    $response['customers'] = $customers;
}


// ===== toggleCustomer (Admin) =====
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

// ===== getCustomerOrders (Admin) =====
elseif ($action === 'getCustomerOrders') {
    requireAdmin();

    $userId = $_POST['userId'] ?? null;
    if (!$userId) {
        $response['message'] = "Kunden-ID fehlt.";
    } else {
        $orders = $userModel->getOrdersByUserId($userId);
        if ($orders !== null) {
            $response['success'] = true;
            $response['orders'] = $orders;
        } else {
            $response['message'] = "Keine Bestellungen gefunden.";
        }
    }
}

// ===== deleteOrderItem (Admin) =====
elseif ($action === 'deleteOrderItem') {
    requireAdmin();

    $orderItemId = $_POST['orderItemId'] ?? null;

    if ($orderItemId) {
        if ($userModel->deleteOrderItem($orderItemId)) {
            $response['success'] = true;
            $response['message'] = "Produkt wurde gelöscht.";
        } else {
            $response['message'] = "Fehler beim Löschen des Produkts.";
        }
    } else {
        $response['message'] = "Keine Positions-ID angegeben.";
    }
}

// ===== updateOrderItemQuantity (Admin) =====
elseif ($action === 'updateOrderItemQuantity') {
    requireAdmin();

    $itemId = $_POST['orderItemId'] ?? null;
    $qty = $_POST['quantity'] ?? null;

    if ($userModel->updateOrderItemQuantity($itemId, $qty)) {
        $response['success'] = true;
    } else {
        $response['message'] = "Fehler beim Aktualisieren.";
    }
}


echo json_encode($response);
exit;
