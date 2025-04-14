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
    
}
