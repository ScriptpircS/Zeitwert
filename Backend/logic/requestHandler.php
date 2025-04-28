<?php
// ==== INITIALISIERUNG & EINBINDUNGEN =====================================

header('Content-Type: application/json');
session_start();

require_once('../config/dbaccess.php');
require_once('../models/product.class.php');
require_once('../models/user.class.php');
require_once('../models/coupon.class.php');
$couponModel = new Coupon();


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

        if (count($result) === 1) {
            
            if ($result[0]['is_active'] == 'inactive') {
                $response['success'] = false;
                $response['message'] = "Dein Account ist deaktiviert. Bitte kontaktiere den Support.";
            } 

            elseif (password_verify($password, $result[0]['password_hash'])) {
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
                $response['success'] = false;
                $response['message'] = "Login fehlgeschlagen – Benutzer oder Passwort ungültig.";
            }
        } else {
            $response['success'] = false;
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


// ==== COUPONS ADMIN - CREATE ====
elseif ($action === 'createCoupon') {
    $wert = $_POST['wert'] ?? null;
    $validUntil = $_POST['valid_until'] ?? null;

    if ($wert !== null && $validUntil !== null) {
        $newCode = $couponModel->createCoupon($wert, $validUntil);
        if ($newCode) {
            $response['success'] = true;
            $response['message'] = "Gutschein erfolgreich erstellt: $newCode";
        } else {
            $response['message'] = "Fehler beim Erstellen des Gutscheins.";
        }
    } else {
        $response['message'] = "Fehlende Eingaben.";
    }
}

// ==== COUPONS ADMIN - LIST ====
elseif ($action === 'listCoupons') {
    $coupons = $couponModel->getAllCoupons();
    $response['success'] = true;
    $response['coupons'] = $coupons;
}

// ==== COUPONS ADMIN - DELETE ====
elseif ($action === 'deleteCoupon') {
    $couponId = $_POST['id'] ?? null;
    if ($couponId) {
        $sql = "DELETE FROM coupons WHERE id = :id";
        $params = [':id' => $couponId];
        if (dbaccess::getInstance()->execute($sql, $params)) {
            $response['success'] = true;
            $response['message'] = "Gutschein erfolgreich gelöscht.";
        } else {
            $response['message'] = "Fehler beim Löschen.";
        }
    } else {
        $response['message'] = "Gutschein-ID fehlt.";
    }
}


// ==== KUNDEN VERWALTEN ===================================================

// Kunden auflisten
elseif ($action === 'listCustomers') {
    $customers = $userModel->getAllCustomers();
    $response['success'] = true;
    $response['customers'] = $customers;
}

// Kundenstatus toggeln (aktiv/inaktiv)
elseif ($action === 'toggleCustomer') {
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

// ==== KUNDENDATEN IN BESTELLFORMULAR EINFÜLLEN ==========================
elseif ($action === 'getUserData') {
    if (isset($_SESSION['username'])) {
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
    } else {
        $response['message'] = "Nicht eingeloggt.";
    }
}

// ==== BESTELLUNG ABSENDEN ===============================================
elseif ($action === 'placeOrder') {
    if (!isset($_SESSION['username'])) {
        $response['message'] = "Du musst eingeloggt sein, um eine Bestellung aufzugeben.";
    } elseif (empty($_SESSION['cart'])) {
        $response['message'] = "Dein Warenkorb ist leer.";
    } else {
        try {
            $db = dbaccess::getInstance();

            // Kundendaten abrufen
            $username = $_SESSION['username'];
            $userResult = $userModel->getByEmailOrUsername($username);

            if (count($userResult) !== 1) {
                throw new Exception("Benutzerdaten konnten nicht geladen werden.");
            }

            $user = $userResult[0];
            $userId = $user['id']; // Hier nehmen wir an: Tabelle user hat 'id'-Feld

            // Gesamtsumme berechnen
            $cart = $_SESSION['cart'];
            $totalAmount = 0;

            foreach ($cart as $item) {
                $totalAmount += $item['product']['preis'] * $item['qty'];
            }

            // 1. Bestellung in "orders" speichern
            $sqlOrder = "INSERT INTO orders (user_id, total_price, order_date, status) VALUES (:user_id, :total_price, NOW(), :status)";
            $paramsOrder = [
                ':user_id' => $userId,
                ':total_price' => $totalAmount,
                ':status' => 'ordered'
            ];

            if (!$db->execute($sqlOrder, $paramsOrder)) {
                throw new Exception("Bestellung konnte nicht gespeichert werden.");
            }

            $orderId = $db->getLastInsertId();

            // 2. Bestellte Produkte in "order_items" speichern
            foreach ($cart as $productId => $item) {
                $sqlItem = "INSERT INTO order_items (order_id, product_id, menge, preis) 
                            VALUES (:order_id, :product_id, :quantity, :price_per_item)";
                $paramsItem = [
                    ':order_id' => $orderId,
                    ':product_id' => $productId,
                    ':quantity' => $item['qty'],
                    ':price_per_item' => $item['product']['preis']
                ];
                $db->execute($sqlItem, $paramsItem);
            }

            // Bestellung abgeschlossen -> Warenkorb leeren
            unset($_SESSION['cart']);

            $response['success'] = true;
            $response['message'] = "Bestellung erfolgreich aufgegeben. Vielen Dank!";
        } catch (Exception $e) {
            $response['message'] = "Fehler bei der Bestellung: " . $e->getMessage();
        }
    }
}



// ==== DEFAULT: UNBEKANNTE AKTION ========================================

else {
    $response['message'] = "Ungültige Aktion.";
}

// ==== JSON-ANTWORT ======================================================

echo json_encode($response);
?>
