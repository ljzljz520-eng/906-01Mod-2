<?php

class AdminLog {
    private $conn;
    private $table_name = "admin_logs";

    public $id;
    public $admin_id;
    public $admin_name;
    public $action_type;
    public $target_type;
    public $target_id;
    public $details;
    public $ip_address;
    public $user_agent;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET 
            admin_id=:admin_id,
            admin_name=:admin_name,
            action_type=:action_type,
            target_type=:target_type,
            target_id=:target_id,
            details=:details,
            ip_address=:ip_address,
            user_agent=:user_agent";
        
        $stmt = $this->conn->prepare($query);
        $this->sanitize();
        
        $stmt->bindValue(":admin_id", $this->admin_id, PDO::PARAM_INT);
        $stmt->bindParam(":admin_name", $this->admin_name);
        $stmt->bindParam(":action_type", $this->action_type);
        $stmt->bindParam(":target_type", $this->target_type);
        $stmt->bindValue(":target_id", $this->target_id, PDO::PARAM_INT);
        $stmt->bindParam(":details", $this->details);
        $stmt->bindParam(":ip_address", $this->ip_address);
        $stmt->bindParam(":user_agent", $this->user_agent);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function findAll($limit = 100, $adminName = null, $actionType = null) {
        $query = "SELECT l.*, u.username as admin_username, u.role as admin_role 
            FROM " . $this->table_name . " l
            LEFT JOIN admin_users u ON l.admin_id = u.id";
        $conditions = [];
        $params = [];

        if ($adminName) {
            $conditions[] = "l.admin_name = :admin_name";
            $params[':admin_name'] = $adminName;
        }
        if ($actionType) {
            $conditions[] = "l.action_type = :action_type";
            $params[':action_type'] = $actionType;
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        $query .= " ORDER BY l.created_at DESC LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function logAction($adminName, $actionType, $targetType, $targetId, $details, $ipAddress = null, $userAgent = null, $adminId = null) {
        $this->admin_id = $adminId;
        $this->admin_name = $adminName;
        $this->action_type = $actionType;
        $this->target_type = $targetType;
        $this->target_id = $targetId;
        $this->details = $details;
        $this->ip_address = $ipAddress ?: ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $this->user_agent = $userAgent ?: ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
        
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
        $this->admin_id = (int)($this->admin_id ?? 0) ?: null;
        $this->admin_name = htmlspecialchars(strip_tags($this->admin_name));
        $this->action_type = htmlspecialchars(strip_tags($this->action_type));
        $this->target_type = htmlspecialchars(strip_tags($this->target_type));
        $this->target_id = (int)$this->target_id;
        $this->details = htmlspecialchars(strip_tags($this->details));
        $this->ip_address = htmlspecialchars(strip_tags($this->ip_address));
        $this->user_agent = htmlspecialchars(strip_tags($this->user_agent ?? ''));
    }
}
