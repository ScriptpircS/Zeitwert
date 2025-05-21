<?php
require_once(__DIR__ . '/../config/dbaccess.php');

class User
{
    private $db;

    public function __construct()
    {
        $this->db = dbaccess::getInstance();
    }

    // Neue Benutzerregistrierung
    public function register($data)
    {
        $sql = "INSERT INTO users (
                    anrede, vorname, nachname, adresse, plz, ort, email, username, password_hash
                ) VALUES (
                    :anrede, :vorname, :nachname, :adresse, :plz, :ort, :email, :username, :password_hash
                )";

        $params = [
            ':anrede' => $data['anrede'],
            ':vorname' => $data['vorname'],
            ':nachname' => $data['nachname'],
            ':adresse' => $data['adresse'],
            ':plz' => $data['plz'],
            ':ort' => $data['ort'],
            ':email' => $data['email'],
            ':username' => $data['username'],
            ':password_hash' => password_hash($data['password'], PASSWORD_DEFAULT)
        ];

        return $this->db->execute($sql, $params);
    }

    // Benutzer anhand von Username oder Email abrufen
    public function getByEmailOrUsername($login)
    {
        $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
        return $this->db->select($sql, [$login, $login]);
    }

    // Benutzername existiert bereits?
    public function usernameExists($username)
    {
        $sql = "SELECT COUNT(*) as anzahl FROM users WHERE username = ?";
        $result = $this->db->select($sql, [$username]);
        return $result[0]['anzahl'] > 0;
    }

    // Email existiert bereits?
    public function emailExists($email)
    {
        $sql = "SELECT COUNT(*) as anzahl FROM users WHERE email = ?";
        $result = $this->db->select($sql, [$email]);
        return $result[0]['anzahl'] > 0;
    }

    // Benutzerdaten updaten
    public function updateUserData($username, $data)
    {
        $allowedFields = ['vorname', 'nachname', 'adresse', 'ort', 'plz', 'anrede', 'zahlungsinfo', 'password_hash'];
        $fieldsToUpdate = [];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fieldsToUpdate[$field] = $data[$field];
            }
        }

        if (empty($fieldsToUpdate))
            return false;

        $sets = [];
        $params = [':username' => $username];
        foreach ($fieldsToUpdate as $field => $value) {
            $sets[] = "$field = :$field";
            $params[":$field"] = $value;
        }

        $setString = implode(', ', $sets);
        $sql = "UPDATE users SET $setString WHERE username = :username";

        return $this->db->execute($sql, $params);
    }

    // === Alle Kunden holen (nur CUSTOMER)
    public function getAllCustomers()
    {
        $sql = "SELECT * FROM users WHERE role = 'customer' ORDER BY created_at DESC";
        return $this->db->select($sql);
    }

    // ========== ADMIN: Toggle Customer Status in DB ==========
    public function toggleCustomerStatus($userId)
    {
        // Aktuellen Status holen
        $sqlSelect = "SELECT is_active FROM users WHERE id = ?";
        $current = $this->db->select($sqlSelect, [$userId]);

        if (count($current) === 1) {
            $currentStatus = $current[0]['is_active']; 
            $newStatus = ($currentStatus === 'active') ? 'inactive' : 'active';

            $sqlUpdate = "UPDATE users SET is_active = ? WHERE id = ?";
            $this->db->execute($sqlUpdate, [$newStatus, $userId]);

            return $newStatus; // 'active' oder 'inactive'
        }

        return null;
    }

    // ========== ADMIN: Orders aus DB holen ==========
    public function getOrdersByUserId($userId)
    {
        // Alle Bestellungen des Nutzers laden
        $sqlOrders = "
        SELECT id, order_date, total_price, status
        FROM orders
        WHERE user_id = :userId
        ORDER BY order_date DESC
    ";
        $orders = $this->db->select($sqlOrders, [':userId' => $userId]);

        // Für jede Bestellung die zugehörigen Artikel mit Produktdetails holen
        foreach ($orders as &$order) {
            $sqlItems = "
            SELECT 
                oi.id,
                oi.product_id,
                oi.menge AS quantity,
                oi.preis AS price,
                p.marke,
                p.modell,
                p.bild_url
            FROM order_items oi
            JOIN products p ON p.id = oi.product_id
            WHERE oi.order_id = :orderId
        ";

            $items = $this->db->select($sqlItems, [':orderId' => $order['id']]);
            $order['items'] = $items;
        }

        return $orders;
    }

    // ========== ADMIN: Delete Orderitem in DB ==========
    public function deleteOrderItem($orderItemId)
    {
        if (!$orderItemId || !is_numeric($orderItemId)) {
            return false;
        }

        // Hole order_id vor dem Löschen
        $sql = "SELECT order_id FROM order_items WHERE id = :id";
        $res = $this->db->select($sql, [':id' => $orderItemId]);

        if (count($res) !== 1)
            return false;

        $orderId = $res[0]['order_id'];

        // Lösche das Item
        $deleteSuccess = $this->db->execute("DELETE FROM order_items WHERE id = :id", [':id' => $orderItemId]);

        if (!$deleteSuccess)
            return false;

        // Aktualisiere Gesamtpreis
        $sumResult = $this->db->select(
            "SELECT SUM(preis * menge) AS total FROM order_items WHERE order_id = :orderId",
            [':orderId' => $orderId]
        );
        $newTotal = $sumResult[0]['total'] ?? 0;

        $this->db->execute(
            "UPDATE orders SET total_price = :total WHERE id = :orderId",
            [
                ':total' => $newTotal,
                ':orderId' => $orderId
            ]
        );

        return true;
    }


    // ========== Update Orderitem Quantity in DB ==========
    public function updateOrderItemQuantity($orderItemId, $qty)
    {
        if (!$orderItemId || !$qty || !is_numeric($qty) || $qty < 1) {
            return false;
        }

        // Menge aktualisieren
        $updateSuccess = $this->db->execute(
            "UPDATE order_items SET menge = :qty WHERE id = :id",
            [':qty' => $qty, ':id' => $orderItemId]
        );

        if (!$updateSuccess) {
            return false;
        }

        // Gesamtpreis neu berechnen
        $this->recalculateOrderTotalByItemId($orderItemId);

        return true;
    }

    // ========== Recalculate Totalprice in DB ==========
    public function recalculateOrderTotalByItemId($orderItemId)
    {
        $sql = "SELECT order_id FROM order_items WHERE id = :id";
        $res = $this->db->select($sql, [':id' => $orderItemId]);

        if (count($res) === 1) {
            $orderId = $res[0]['order_id'];

            $sumResult = $this->db->select(
                "SELECT SUM(preis * menge) AS total FROM order_items WHERE order_id = :orderId",
                [':orderId' => $orderId]
            );

            $newTotal = $sumResult[0]['total'] ?? 0;

            $this->db->execute(
                "UPDATE orders SET total_price = :total WHERE id = :orderId",
                [
                    ':total' => $newTotal,
                    ':orderId' => $orderId
                ]
            );
        }
    }



}
?>