<?php
require_once(__DIR__ . '/../config/dbaccess.php');

class User {
    private $db;

    public function __construct() {
        $this->db = dbaccess::getInstance();
    }

    // Neue Benutzerregistrierung
    public function register($data) {
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
    public function getByEmailOrUsername($login) {
        $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
        return $this->db->select($sql, [$login, $login]);
    }

    // Benutzername existiert bereits?
    public function usernameExists($username) {
        $sql = "SELECT COUNT(*) as anzahl FROM users WHERE username = ?";
        $result = $this->db->select($sql, [$username]);
        return $result[0]['anzahl'] > 0;
    }

    // Email existiert bereits?
    public function emailExists($email) {
        $sql = "SELECT COUNT(*) as anzahl FROM users WHERE email = ?";
        $result = $this->db->select($sql, [$email]);
        return $result[0]['anzahl'] > 0;
    }
    // === Alle Kunden holen (nur CUSTOMER)
    public function getAllCustomers() {
        $sql = "SELECT * FROM users WHERE role = 'customer' ORDER BY created_at DESC";
        return $this->db->select($sql);
    }

    // === Kunden aktivieren/deaktivieren
    public function toggleCustomerStatus($userId) {
        // Aktuellen Status holen
        $sqlSelect = "SELECT is_active FROM users WHERE id = ?";
        $current = $this->db->select($sqlSelect, [$userId]);
    
        if (count($current) === 1) {
            $currentStatus = $current[0]['is_active']; // 'active' oder 'inactive'
            $newStatus = ($currentStatus === 'active') ? 'inactive' : 'active';
    
            $sqlUpdate = "UPDATE users SET is_active = ? WHERE id = ?";
            $this->db->execute($sqlUpdate, [$newStatus, $userId]);
    
            return $newStatus; // 'active' oder 'inactive'
        }
    
        return null; // Benutzer nicht gefunden
    }
    
}
?>
