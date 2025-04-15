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

// ==== AUTO-LOGIN =========================================================
if ($action === 'autoLogin') {
    $loginCredentials = $_POST['loginCredentials'] ?? '';

    if (empty($loginCredentials)) {
        $response['message'] = "Kein Benutzer gespeichert.";
    } else {
        $result = $userModel->getByEmailOrUsername($loginCredentials);

        if (count($result) === 1) {
            $_SESSION["username"] = $result[0]['username'];

            // Setze den stayLoggedIn-Cookie im Backend
            setcookie("stayLoggedIn", $loginCredentials, [
                'expires' => time() + (86400 * 30), // 30 Tage
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


// ==== LOGIN =============================================================

if ($action === 'login') {
    $loginCredentials = $_POST['loginCredentials'] ?? '';
    $password = $_POST['password'] ?? '';
    $stayLoggedIn = $_POST['stayLoggedIn'] ?? false;

    if (empty($loginCredentials) || empty($password)) {
        $response['message'] = "Bitte füllen Sie alle Felder aus.";
    } else {
        $result = $userModel->getByEmailOrUsername($loginCredentials);

        if (count($result) === 1 && password_verify($password, $result[0]['password_hash'])) {
            $_SESSION["username"] = $result[0]['username'];
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

// ==== IN DEN WARENKORB LEGEN ============================================

} elseif ($action === 'addToCart') {
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
        }
        else {
            $response['message'] = "Produkt wurde nicht gefunden.";
        }
    } else {
        $response['message'] = "Produkt-ID fehlt.";
    
    }
// ==== WARENKORB ANZEIGEN ===============================================

} elseif ($action === 'getCart') {
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

// ==== WARENKORB VERÄNDERN ===============================================

} elseif ($action === 'removeFromCart') {
    unset($_SESSION['cart'][$_POST['productId']]);

} elseif ($action === 'updateCartQuantity') {
    $_SESSION['cart'][$_POST['productId']]['qty'] = $_POST['quantity'];


// ==== PRODUKTÜBERSICHT LADEN ============================================

} elseif ($action === 'getProducts') {
    $products = $productModel->getAllProducts();

    $response['success'] = true;
    $response['products'] = $products;


} elseif ($action === 'getProductsByCategory') {
    $catId = $_POST['categoryId'] ?? null;

    if ($catId !== null) {
        $products = $productModel->getProductsByCategory($catId);
        $response['success'] = true;
        $response['products'] = $products;
    } else {
        $response['message'] = "Keine Kategorie-ID übergeben.";
    }

// ==== PRODUKTE FILTERN ==================================================

} elseif ($action === 'searchProducts') {
    $query = $_POST['query'] ?? '';
    $results = $productModel->searchProducts($query);

    if(empty($results)) {
        $response['success'] = false;
        $response['message'] = "Keine passenden Produkte gefunden!";
    } else {
        $response['success'] = true;
        $response['products'] = $results;
    }
}


// ==== DEFAULT: UNBEKANNTE AKTION ========================================

else {
    $response['message'] = "Ungültige Aktion.";
}

// ==== JSON-ANTWORT ======================================================

echo json_encode($response);
?>
