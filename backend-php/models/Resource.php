<?php

class Resource {
    private $conn;
    private $table_name = "resources";

    public $id;
    public $name;
    public $description;
    public $original_url;
    public $category;
    public $size;
    public $checksum;
    public $checksum_type;
    public $original_updated_at;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function findAll($category = null) {
        $query = "SELECT r.*, 
            (SELECT COUNT(*) FROM mirror_sync m WHERE m.resource_id = r.id) as mirror_count,
            (SELECT COUNT(*) FROM mirror_sync m WHERE m.resource_id = r.id AND m.sync_status = 'synced' AND m.available = 1) as healthy_mirror_count
            FROM " . $this->table_name . " r";
        
        if ($category) {
            $query .= " WHERE r.category = :category";
        }
        $query .= " ORDER BY r.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        
        if ($category) {
            $category = htmlspecialchars(strip_tags($category));
            $stmt->bindParam(":category", $category);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findOne($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    public function search($keyword, $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        $keyword = "%$keyword%";
        
        $query = "SELECT r.*, 
            (SELECT COUNT(*) FROM mirror_sync m WHERE m.resource_id = r.id) as mirror_count,
            (SELECT COUNT(*) FROM mirror_sync m WHERE m.resource_id = r.id AND m.sync_status = 'synced' AND m.available = 1) as healthy_mirror_count
            FROM " . $this->table_name . " r 
            WHERE r.name LIKE :keyword OR r.description LIKE :keyword OR r.category LIKE :keyword
            ORDER BY r.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":keyword", $keyword);
        $stmt->bindValue(":limit", (int)$perPage, PDO::PARAM_INT);
        $stmt->bindValue(":offset", (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET 
            name=:name, 
            description=:description, 
            original_url=:original_url, 
            category=:category, 
            size=:size, 
            checksum=:checksum, 
            checksum_type=:checksum_type, 
            original_updated_at=:original_updated_at";
        
        $stmt = $this->conn->prepare($query);

        $this->sanitize();
        
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":original_url", $this->original_url);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":size", $this->size);
        $stmt->bindParam(":checksum", $this->checksum);
        $stmt->bindParam(":checksum_type", $this->checksum_type);
        $stmt->bindParam(":original_updated_at", $this->original_updated_at);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function update($id) {
        $query = "UPDATE " . $this->table_name . " SET 
            name=:name, 
            description=:description, 
            original_url=:original_url, 
            category=:category, 
            size=:size, 
            checksum=:checksum, 
            checksum_type=:checksum_type, 
            original_updated_at=:original_updated_at
            WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);

        $this->sanitize();
        
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":original_url", $this->original_url);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":size", $this->size);
        $stmt->bindParam(":checksum", $this->checksum);
        $stmt->bindParam(":checksum_type", $this->checksum_type);
        $stmt->bindParam(":original_updated_at", $this->original_updated_at);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    private function sanitize() {
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->original_url = htmlspecialchars(strip_tags($this->original_url));
        $this->category = htmlspecialchars(strip_tags($this->category));
        $this->size = htmlspecialchars(strip_tags($this->size));
        $this->checksum = htmlspecialchars(strip_tags($this->checksum));
        $this->checksum_type = htmlspecialchars(strip_tags($this->checksum_type));
        $this->original_updated_at = htmlspecialchars(strip_tags($this->original_updated_at));
    }

    public function getCategories() {
        $query = "SELECT DISTINCT category FROM " . $this->table_name . " WHERE category IS NOT NULL AND category != '' ORDER BY category";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
