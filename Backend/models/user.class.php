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

    // Benutzerdaten updaten
    public function updateUserData($username, $data) {
        $allowedFields = ['vorname', 'nachname', 'adresse', 'ort', 'plz', 'anrede', 'zahlungsinfo', 'password_hash'];
        $fieldsToUpdate = [];
    
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fieldsToUpdate[$field] = $data[$field];
            }
        }
    
        if (empty($fieldsToUpdate)) return false;
    
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
        
}
