<?php

class Favorite {
    private $conn;
    private $table_name = "favorites";

    public $id;
    public $name;
    public $magnet;
    public $size;
    public $seeders;
    public $leechers;
    public $category;
    public $source;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create favorite
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET name=:name, magnet=:magnet, size=:size, seeders=:seeders, leechers=:leechers, category=:category, source=:source";
        
        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->magnet = htmlspecialchars(strip_tags($this->magnet));
        $this->size = htmlspecialchars(strip_tags($this->size));
        $this->seeders = htmlspecialchars(strip_tags($this->seeders));
        $this->leechers = htmlspecialchars(strip_tags($this->leechers));
        $this->category = htmlspecialchars(strip_tags($this->category));
        $this->source = htmlspecialchars(strip_tags($this->source));

        // Bind data
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":magnet", $this->magnet);
        $stmt->bindParam(":size", $this->size);
        $stmt->bindParam(":seeders", $this->seeders);
        $stmt->bindParam(":leechers", $this->leechers);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":source", $this->source);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Check if exists
    public function findOneByMagnet($magnet) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE magnet = :magnet LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":magnet", $magnet);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get all favorites
    public function findAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Delete favorite
    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        
        if($stmt->execute()) {
            // Check if any row was actually deleted
            if($stmt->rowCount() > 0) {
                return true;
            }
        }
        return false;
    }
}
