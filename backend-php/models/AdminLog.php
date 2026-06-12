<?php

class AdminLog {
    private $conn;
    private $table_name = "admin_logs";

    public $id;
    public $admin_name;
    public $action_type;
    public $target_type;
    public $target_id;
    public $details;
    public $ip_address;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET 
            admin_name=:admin_name,
            action_type=:action_type,
            target_type=:target_type,
            target_id=:target_id,
            details=:details,
            ip_address=:ip_address";
        
        $stmt = $this->conn->prepare($query);
        $this->sanitize();
        
        $stmt->bindParam(":admin_name", $this->admin_name);
        $stmt->bindParam(":action_type", $this->action_type);
        $stmt->bindParam(":target_type", $this->target_type);
        $stmt->bindValue(":target_id", $this->target_id, PDO::PARAM_INT);
        $stmt->bindParam(":details", $this->details);
        $stmt->bindParam(":ip_address", $this->ip_address);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function findAll($limit = 100, $adminName = null, $actionType = null) {
        $query = "SELECT * FROM " . $this->table_name;
        $conditions = [];
        $params = [];

        if ($adminName) {
            $conditions[] = "admin_name = :admin_name";
            $params[':admin_name'] = $adminName;
        }
        if ($actionType) {
            $conditions[] = "action_type = :action_type";
            $params[':action_type'] = $actionType;
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        $query .= " ORDER BY created_at DESC LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function logAction($adminName, $actionType, $targetType, $targetId, $details, $ipAddress = null) {
        $this->admin_name = $adminName;
        $this->action_type = $actionType;
        $this->target_type = $targetType;
        $this->target_id = $targetId;
        $this->details = $details;
        $this->ip_address = $ipAddress ?: ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        
        return $this->create();
    }

    public function getStats() {
        $query = "SELECT 
            action_type,
            COUNT(*) as count
            FROM " . $this->table_name . "
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY action_type";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function sanitize() {
        $this->admin_name = htmlspecialchars(strip_tags($this->admin_name));
        $this->action_type = htmlspecialchars(strip_tags($this->action_type));
        $this->target_type = htmlspecialchars(strip_tags($this->target_type));
        $this->target_id = (int)$this->target_id;
        $this->details = htmlspecialchars(strip_tags($this->details));
        $this->ip_address = htmlspecialchars(strip_tags($this->ip_address));
    }
}
