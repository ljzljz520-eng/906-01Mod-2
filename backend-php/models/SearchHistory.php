<?php

class SearchHistory {
    private $conn;
    private $table_name = "search_history";

    public $id;
    public $keyword;
    public $query;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new search history record
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET keyword=:keyword, query=:query";
        $stmt = $this->conn->prepare($query);

        $this->keyword = htmlspecialchars(strip_tags($this->keyword));
        $this->query = htmlspecialchars(strip_tags($this->query));

        $stmt->bindParam(":keyword", $this->keyword);
        $stmt->bindParam(":query", $this->query);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Get all history
    public function findAll($limit = 20) {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        
        // PDO LIMIT requires integer type
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    // Delete all history
    public function deleteAll() {
        $query = "DELETE FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
