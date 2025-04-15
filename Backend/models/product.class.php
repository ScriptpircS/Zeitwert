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
        $sql = "SELECT * FROM products 
                WHERE LOWER(marke) LIKE ? 
                   OR LOWER(modell) LIKE ? 
                   OR LOWER(beschreibung) LIKE ?";
        
        $likeQuery = '%' . strtolower($query) . '%';
        return $this->db->select($sql, [$likeQuery, $likeQuery, $likeQuery]);
    }
    
    
}
