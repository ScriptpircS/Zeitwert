<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once(__DIR__ . '/../../config/dbaccess.php');
require_once(__DIR__ . '/../../models/product.class.php');
require_once(__DIR__ . '/../utils/auth.php');

$productModel = new Product();
$action = $_POST['action'] ?? '';
$response = ['success' => false];

// Öffentliche Produktabfragen
if ($action === 'getProducts') {
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

elseif ($action === 'getProductsByCategory') {
    $catId = $_POST['categoryId'] ?? null;
    if ($catId !== null) {
        $products = $productModel->getProductsByCategory($catId);
        $response['success'] = true;
        $response['products'] = $products;
    } else {
        $response['message'] = "Keine Kategorie-ID übergeben.";
    }
}

elseif ($action === 'searchProducts') {
    $query = $_POST['query'] ?? '';
    $results = $productModel->searchProducts($query);
    if (empty($results)) {
        $response['success'] = false;
        $response['message'] = "Keine passenden Produkte gefunden!";
    } else {
        $response['success'] = true;
        $response['products'] = $results;
    }
}

// ========== ADMIN: CREATE ==========
elseif ($action === 'createProduct') {
    requireAdmin();

    $productData = [
        'marke' => $_POST['marke'] ?? '',
        'modell' => $_POST['modell'] ?? '',
        'beschreibung' => $_POST['beschreibung'] ?? '',
        'preis' => $_POST['preis'] ?? 0,
        'referenz' => $_POST['referenz'] ?? null,
        'lunette' => $_POST['lunette'] ?? null,
        'gehaeuse' => $_POST['gehaeuse'] ?? null,
        'uhrwerk' => $_POST['uhrwerk'] ?? null,
        'armband' => $_POST['armband'] ?? null,
        'schliesse' => $_POST['schliesse'] ?? null,
        'merkmale' => $_POST['merkmale'] ?? null,
        'wasserdicht' => $_POST['wasserdicht'] ?? null
    ];

    $bild_url = null;
    if (isset($_FILES['bild']) && $_FILES['bild']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['bild']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $productData['modell']);
            $imageName = $safeName . '.' . $ext;
            $uploadPath = __DIR__ . '/../../productpictures/' . $imageName;
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

// ========== ADMIN: UPDATE ==========
elseif ($action === 'updateProduct') {
    requireAdmin();

    $productData = [
        'id' => $_POST['id'] ?? '',
        'marke' => $_POST['marke'] ?? '',
        'modell' => $_POST['modell'] ?? '',
        'beschreibung' => $_POST['beschreibung'] ?? '',
        'preis' => $_POST['preis'] ?? '',
        'referenz' => $_POST['referenz'] ?? null,
        'lunette' => $_POST['lunette'] ?? null,
        'gehaeuse' => $_POST['gehaeuse'] ?? null,
        'uhrwerk' => $_POST['uhrwerk'] ?? null,
        'armband' => $_POST['armband'] ?? null,
        'schliesse' => $_POST['schliesse'] ?? null,
        'merkmale' => $_POST['merkmale'] ?? null,
        'wasserdicht' => $_POST['wasserdicht'] ?? null
    ];

    if (isset($_FILES['bild']) && $_FILES['bild']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['bild']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $productData['modell']);
            $imageName = $safeName . '.' . $ext;
            $uploadPath = __DIR__ . '/../../productpictures/' . $imageName;
            move_uploaded_file($_FILES['bild']['tmp_name'], $uploadPath);
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

// ========== ADMIN: DELETE ==========
elseif ($action === 'deleteProduct') {
    requireAdmin();

    $productId = $_POST['id'] ?? null;
    if ($productId && $productModel->deleteProduct($productId)) {
        $response['success'] = true;
        $response['message'] = "Produkt erfolgreich gelöscht.";
    } else {
        $response['message'] = "Fehler beim Löschen.";
    }
}

elseif ($action === 'deleteImage') {
    requireAdmin();

    $productId = $_POST['id'] ?? null;
    if ($productId) {
        $product = $productModel->getProductById($productId);
        if ($product && !empty($product['bild_url'])) {
            $filePath = __DIR__ . '/../../productpictures/' . $product['bild_url'];
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

echo json_encode($response);
exit;
