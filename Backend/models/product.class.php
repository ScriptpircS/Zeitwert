<?php

require_once(__DIR__ . '/../config/dbaccess.php');

class Product {
    private $db;

    public function __construct() {
        $this->db = dbaccess::getInstance();
    }

    public function getAllProducts() {
        $sql = "SELECT * FROM products";
        return $this->db->select($sql);
    }

    public function getProductsByCategory($categoryId) {
        $sql = "SELECT * FROM products WHERE category_id = ?";
        return $this->db->select($sql, [$categoryId]);
    }

    public function getProductById($id) {
        $sql = "SELECT * FROM products WHERE id = :id";
        $params = [':id' => $id];
    
        return $this->db->select($sql, $params)[0] ?? null;
    }
    
    public function searchProducts($query) {
        $likeQuery = strtolower($query) . '%';

        $sql = "SELECT * FROM products 
                WHERE 
                    LOWER(marke) LIKE ? OR
                    LOWER(marke) LIKE ? OR
                    LOWER(modell) LIKE ? OR
                    LOWER(modell) LIKE ? OR
                    LOWER(referenz) LIKE ? OR
                    LOWER(referenz) LIKE ?";

        return $this->db->select($sql, [
            $likeQuery,      
            '% ' . $likeQuery, 
            $likeQuery,
            '% ' . $likeQuery,
            $likeQuery,
            '% ' . $likeQuery
        ]);
    }

    public function createProduct($data) {
        $sql = "INSERT INTO products (
            marke, modell, beschreibung, preis, bild_url,
            referenz, lunette, gehaeuse, uhrwerk, armband,
            schliesse, merkmale, wasserdicht
        ) VALUES (
            :marke, :modell, :beschreibung, :preis, :bild_url,
            :referenz, :lunette, :gehaeuse, :uhrwerk, :armband,
            :schliesse, :merkmale, :wasserdicht
        )";
        return $this->db->execute($sql, $data);
    }

    public function updateProduct($data) {
        $set = "
                marke = :marke,
                modell = :modell,
                beschreibung = :beschreibung,
                preis = :preis,
                referenz = :referenz,
                lunette = :lunette,
                gehaeuse = :gehaeuse,
                uhrwerk = :uhrwerk,
                armband = :armband,
                schliesse = :schliesse,
                merkmale = :merkmale,
                wasserdicht = :wasserdicht
            ";
        if (isset($data['bild_url'])) {
            $set .= ", bild_url = :bild_url";
        }
        $sql = "UPDATE products SET $set WHERE id = :id";
        return $this->db->execute($sql, $data);
    }

    public function deleteProduct($id) {
        $sql = "DELETE FROM products WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }

    public function deleteProductImage($id) {
        $sql = "UPDATE products SET bild_url = NULL WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }
    
    
}
