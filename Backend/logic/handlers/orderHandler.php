<?php
if (session_status() === PHP_SESSION_NONE)
    session_start();

require_once(__DIR__ . '/../../config/dbaccess.php');
require_once(__DIR__ . '/../../models/user.class.php');
require_once(__DIR__ . '/../../models/coupon.class.php');
require_once(__DIR__ . '/../utils/auth.php');

$userModel = new User();
$couponModel = new Coupon();
$db = dbaccess::getInstance();
$action = $_POST['action'] ?? '';
$response = ['success' => false];

// ===== Bestellung absenden (nur eingeloggt) =====
if ($action === 'placeOrder') {
    requireLogin();

    if (empty($_SESSION['cart'])) {
        $response['message'] = "Dein Warenkorb ist leer.";
    } else {
        try {
            $username = $_SESSION['username'];
            $userResult = $userModel->getByEmailOrUsername($username);

            if (count($userResult) !== 1)
                throw new Exception("Benutzerdaten konnten nicht geladen werden.");

            $user = $userResult[0];
            $userId = $user['id'];

            $cart = $_SESSION['cart'];
            $totalAmount = 0;

            foreach ($cart as $item) {
                $totalAmount += $item['product']['preis'] * $item['qty'];
            }

            // Gutschein anwenden, wenn vorhanden
            if (!empty($_POST['coupon_code'])) {
                $code = $_POST['coupon_code'];
                $coupon = $couponModel->getCouponByCode($code);

                if ($coupon && $coupon['status'] === 'offen' && $coupon['valid_until'] >= date('Y-m-d')) {
                    $totalAmount -= floatval($coupon['wert']);
                    if ($totalAmount < 0)
                        $totalAmount = 0;
                    $couponModel->markCouponAsUsed($code);
                }
            }

            // Bestellung speichern
            $paymentMethodId = $_POST['payment_method'] ?? null;
            if (!$paymentMethodId)
                throw new Exception("Keine Zahlungsart gewählt.");

            // Prüfen, ob Zahlungsart zum User gehört
            $stmt = $pdo->prepare("SELECT id FROM payment_info WHERE id = ? AND user_id = ?");
            $stmt->execute([$paymentMethodId, $userId]);
            if ($stmt->rowCount() === 0) {
                throw new Exception("Ungültige Zahlungsart.");
            }

            $sqlOrder = "INSERT INTO orders (user_id, total_price, order_date, status, payment_info_id) 
                         VALUES (:user_id, :total_price, NOW(), :status, :payment_info_id)";
            $paramsOrder = [
                ':user_id' => $userId,
                ':total_price' => $totalAmount,
                ':status' => 'pending',
                ':payment_info_id' => $paymentMethodId
            ];

            if (!$db->execute($sqlOrder, $paramsOrder)) {
                throw new Exception("Bestellung konnte nicht gespeichert werden.");
            }

            $orderId = $db->getLastInsertId();

            // Artikel speichern
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

            unset($_SESSION['cart']);

            $response['success'] = true;
            $response['message'] = "Bestellung erfolgreich aufgegeben. Vielen Dank!";
        } catch (Exception $e) {
            $response['message'] = "Fehler bei der Bestellung: " . $e->getMessage();
        }
    }
}


// ===== Bestellungen laden (nur eingeloggt) =====
elseif ($action === 'loadOrders') {
    requireLogin();

    try {
        $username = $_SESSION['username'];
        $userResult = $userModel->getByEmailOrUsername($username);
        if (count($userResult) !== 1)
            throw new Exception("Benutzerdaten konnten nicht geladen werden.");

        $user = $userResult[0];
        $userId = $user['id'];

        $sql = "SELECT id AS orderId, total_price, order_date, status 
                FROM orders 
                WHERE user_id = ? 
                ORDER BY order_date DESC";

        $orders = $db->select($sql, [$userId]);

        $response['success'] = true;
        $response['orders'] = $orders;
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
}


// ===== Bestellpositionen anzeigen (nur eingeloggt) =====
elseif ($action === 'loadOrderItems') {
    requireLogin();

    $orderId = $_POST['orderId'] ?? null;

    if (!$orderId) {
        $response['message'] = "Keine Bestell-ID angegeben.";
    } else {
        try {
            $sql = "
                SELECT 
                    CONCAT(p.marke, ' ', p.modell) AS produktname,
                    oi.menge,
                    oi.preis
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?
            ";

            $items = $db->select($sql, [$orderId]);

            $response['success'] = true;
            $response['items'] = $items;
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
    }
}

// ===== Zahlungsmöglichkeiten anzeigen (nur eingeloggt) =====
elseif ($action === 'getUserPaymentMethods') {
    requireLogin();
    $userId = $_SESSION['user_id'];

    $stmt = $pdo->prepare("SELECT id, type, details FROM payment_info WHERE user_id = ?");
    $stmt->execute([$userId]);
    $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'methods' => $methods
    ]);
    exit;
}

echo json_encode($response);
exit;
