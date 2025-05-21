<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


session_start();
header('Content-Type: application/json');

require_once('../config/dbaccess.php');
require_once('./utils/auth.php');

$action = $_POST['action'] ?? '';
$response = ['success' => false];

switch ($action) {
    // Authentifizierung
    case 'login':
    case 'register':
    case 'autoLogin':
        require './handlers/authHandler.php';
        break;

    //productHandler
    case 'getProducts':
    case 'getProduct':
    case 'createProduct':
    case 'updateProduct':
    case 'deleteProduct':
    case 'deleteImage':
    case 'searchProducts':
    case 'getProductsByCategory':
        require './handlers/productHandler.php';
        break;

    //userHandler
    case 'getUserData':
    case 'listCustomers':
    case 'toggleCustomer':
    case 'getCustomerOrders':
    case 'deleteOrderItem':    
    case 'updateOrderItemQuantity':
        require './handlers/userHandler.php';
        break;

    //cartHandler
    case 'getCart':
    case 'addToCart':
    case 'removeFromCart':
    case 'updateCartQuantity':
        require './handlers/cartHandler.php';
        break;

    //couponHandler
    case 'createCoupon':
    case 'listCoupons':
    case 'deleteCoupon':
    case 'validateCoupon':
    case 'useCoupon':
        require './handlers/couponHandler.php';
        break;

    //orderHandler    
    case 'placeOrder':
    case 'loadOrders':
    case 'loadOrderItems':
        require './handlers/orderHandler.php';
        break;

    //accountHandler
    case 'getAccountData':
    case 'updateAddress':
    case 'updatePayment':
    case 'loadPaymentMethods':
    case 'addPaymentMethod':
    case 'getPaymentMethod':
    case 'updatePaymentMethod':
    case 'deletePaymentMethod':
    case 'changePassword':
        require './handlers/accountHandler.php';
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Ungültige Aktion.']);
        break;
}
