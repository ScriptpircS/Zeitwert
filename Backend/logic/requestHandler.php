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
            $_SESSION["role"] = $result[0]['role'];

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
            $_SESSION["role"] = $result[0]['role'];
            
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

// ==== PRODUKTE ADMIN - READ ====

elseif ($action === 'listProducts') {
    $products = $productModel->getAllProducts();
    $response['success'] = true;
    $response['products'] = $products;
}

elseif ($action === 'getProduct') {
    $productId = $_POST['id'] ?? null;
    if ($productId) {
        $product = $productModel->getProductById($productId);
        if ($product) {
            $response['success'] = true;
            $response['product'] = $product;
        } else {
            $response['message'] = "Produkt nicht gefunden.";
        }
    } else {
        $response['message'] = "Produkt-ID fehlt.";
    }
}

// ==== PRODUKTE ADMIN - CREATE ====

elseif ($action === 'createProduct') {
    $productData = [
        'marke' => $_POST['marke'] ?? '',
        'modell' => $_POST['modell'] ?? '',
        'beschreibung' => $_POST['beschreibung'] ?? '',
        'preis' => $_POST['preis'] ?? 0,
    ];

    $bild_url = null;
    if (isset($_FILES['bild']) && $_FILES['bild']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['bild']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
       
            $modell = isset($_POST['modell']) ? $_POST['modell'] : 'default';
            $modell = preg_replace('/[^a-zA-Z0-9_-]/', '_', $modell); // nur sichere Zeichen
            
            $imageName = $modell . '.' . $ext;
            $uploadPath = __DIR__ . '/../productpictures/' . $imageName;
    
            move_uploaded_file($_FILES['bild']['tmp_name'], $uploadPath);
    
            $bild_url = $imageName;
        }
    }
    
    $productData['bild_url'] = $bild_url;

    if ($productModel->createProduct($productData)) {
        $response['success'] = true;
        $response['message'] = "Produkt erfolgreich erstellt.";
    } else {
        $response['message'] = "Fehler beim Erstellen.";
    }
}

// ==== PRODUKTE ADMIN - UPDATE ====

elseif ($action === 'updateProduct') {
    $productData = [
        'id' => $_POST['id'] ?? '',
        'marke' => $_POST['marke'] ?? '',
        'modell' => $_POST['modell'] ?? '',
        'beschreibung' => $_POST['beschreibung'] ?? '',
        'preis' => $_POST['preis'] ?? '',
    ];

    if (isset($_FILES['bild']) && $_FILES['bild']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['bild']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $modell = isset($_POST['modell']) ? $_POST['modell'] : 'default';
            $modell = preg_replace('/[^a-zA-Z0-9_-]/', '_', $modell); // nur sichere Zeichen
            
            $imageName = $modell . '.' . $ext;
            $uploadFolder = __DIR__ . '/../productpictures/';
            move_uploaded_file($_FILES['bild']['tmp_name'], $uploadFolder . $imageName);
            $productData['bild_url'] = $imageName;
        }
    }

    if ($productModel->updateProduct($productData)) {
        $response['success'] = true;
        $response['message'] = "Produkt erfolgreich aktualisiert.";
    } else {
        $response['message'] = "Fehler beim Aktualisieren.";
    }
}

// ==== PRODUKTE ADMIN - DELETE ====

elseif ($action === 'deleteProduct') {
    $productId = $_POST['id'] ?? null;
    if ($productId && $productModel->deleteProduct($productId)) {
        $response['success'] = true;
        $response['message'] = "Produkt erfolgreich gelöscht.";
    } else {
        $response['message'] = "Fehler beim Löschen.";
    }
}

elseif ($action === 'deleteImage') {
    $productId = $_POST['id'] ?? null;
    if ($productId) {
        $product = $productModel->getProductById($productId);
        if ($product && !empty($product['bild_url'])) {
            $filePath = __DIR__ . '/../productpictures/' . $product['bild_url'];
            if (file_exists($filePath)) {
                unlink($filePath);
                if ($productModel->deleteProductImage($productId)) {
                    $response['success'] = true;
                    $response['message'] = "Bild erfolgreich gelöscht.";
                } else {
                    $response['message'] = "Fehler beim Löschen aus DB.";
                }
            } else {
                $response['message'] = "Bilddatei existiert nicht.";
            }
        } else {
            $response['message'] = "Produkt oder Bild nicht gefunden.";
        }
    } else {
        $response['message'] = "Produkt-ID fehlt.";
    }
}


// ==== DEFAULT: UNBEKANNTE AKTION ========================================

else {
    $response['message'] = "Ungültige Aktion.";
}

// ==== JSON-ANTWORT ======================================================

echo json_encode($response);
?>
