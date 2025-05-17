<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once(__DIR__ . '/../../config/dbaccess.php');
require_once(__DIR__ . '/../../models/product.class.php');
require_once(__DIR__ . '/../utils/auth.php');

$productModel = new Product();
$action = $_POST['action'] ?? '';
$response = ['success' => false];

// ==== GET CART =======================================================
if ($action === 'getCart') {
    $cart = $_SESSION['cart'] ?? [];

    $gesamtpreis = 0;
    $gesamtmenge = 0;

    foreach ($cart as $item) {
        $gesamtpreis += $item['product']['preis'] * $item['qty'];
        $gesamtmenge += $item['qty'];
    }

    $response['success'] = true;
    $response['cart'] = $cart;
    $response['gesamtpreis'] = $gesamtpreis;
    $response['gesamtmenge'] = $gesamtmenge;
}

// ==== ADD TO CART ===================================================
elseif ($action === 'addToCart') {
    $productId = $_POST['productId'] ?? null;

    if ($productId !== null) {
        $product = $productModel->getProductById($productId);

        if ($product) {
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }

            if (!isset($_SESSION['cart'][$productId])) {
                $_SESSION['cart'][$productId] = ['qty' => 1, 'product' => $product];
            } else {
                $_SESSION['cart'][$productId]['qty']++;
            }

            $totalQty = array_sum(array_column($_SESSION['cart'], 'qty'));
            $response = ['success' => true, 'cartCount' => $totalQty];
        } else {
            $response['message'] = "Produkt wurde nicht gefunden.";
        }
    } else {
        $response['message'] = "Produkt-ID fehlt.";
    }
}

// ==== REMOVE FROM CART ==============================================
elseif ($action === 'removeFromCart') {
    $productId = $_POST['productId'] ?? null;

    if ($productId !== null && isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
        $response['success'] = true;
    } else {
        $response['message'] = "Produkt nicht im Warenkorb.";
    }
}

// ==== UPDATE CART QUANTITY ==========================================
elseif ($action === 'updateCartQuantity') {
    $productId = $_POST['productId'] ?? null;
    $quantity = intval($_POST['quantity'] ?? 1);

    if ($productId !== null && isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId]['qty'] = $quantity;
        $response['success'] = true;
    } else {
        $response['message'] = "Produkt nicht im Warenkorb.";
    }
}

echo json_encode($response);
exit;
