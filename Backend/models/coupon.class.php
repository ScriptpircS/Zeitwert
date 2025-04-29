<?php
require_once(__DIR__ . '/../config/dbaccess.php');

class Coupon {
    private $db;

    public function __construct() {
        $this->db = dbaccess::getInstance();
    }

    // Neuen Gutschein erstellen
    public function createCoupon($wert, $validUntil) {
        $code = $this->generateRandomCode();
        $sql = "INSERT INTO coupons (code, wert, valid_until, status) VALUES (:code, :wert, :valid_until, 'offen')";
        $params = [
            ':code' => $code,
            ':wert' => $wert,
            ':valid_until' => $validUntil
        ];
        $success = $this->db->execute($sql, $params);
        return $success ? $code : false;
    }

    // Alle Gutscheine abrufen
    public function getAllCoupons() {
        $sql = "SELECT * FROM coupons";
        return $this->db->select($sql);
    }

    // Gutscheinstatus aktualisieren (optional)
    public function updateCouponStatus($id, $status) {
        $sql = "UPDATE coupons SET status = :status WHERE id = :id";
        $params = [
            ':status' => $status,
            ':id' => $id
        ];
        return $this->db->execute($sql, $params);
    }

     // Gutschein anhand Code holen
     public function getCouponByCode($code) {
        $sql = "SELECT * FROM coupons WHERE UPPER(code) = UPPER(:code) LIMIT 1";
        $params = [':code' => $code];
        $result = $this->db->select($sql, $params);
        return count($result) === 1 ? $result[0] : null;
    }
    

    // Gutschein als eingelöst markieren
    public function markCouponAsUsed($code) {
        $sql = "UPDATE coupons SET status = 'eingelöst' WHERE code = :code";
        $params = [':code' => $code];
        return $this->db->execute($sql, $params);
    }

    // Hilfsfunktion: 5-stelligen Zufallscode generieren
    private function generateRandomCode() {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        for ($i = 0; $i < 5; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $code;
    }
}
?>
