<?php

class AdminUser {
    private $conn;
    private $table_name = "admin_users";

    public $id;
    public $username;
    public $password_hash;
    public $display_name;
    public $role;
    public $last_login_at;
    public $last_login_ip;
    public $is_active;
    public $created_at;
    public $updated_at;

    const SESSION_TTL = 86400;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function findByUsername($username) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $username = htmlspecialchars(strip_tags($username));
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function findById($id) {
        $query = "SELECT id, username, display_name, role, last_login_at, is_active, created_at FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function findAll() {
        $query = "SELECT id, username, display_name, role, last_login_at, last_login_ip, is_active, created_at FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function login($username, $password, $ipAddress = null) {
        $user = $this->findByUsername($username);

        if (!$user) {
            return ['success' => false, 'message' => '用户名或密码错误', 'code' => 'INVALID_CREDENTIALS'];
        }

        if (!$user['is_active']) {
            return ['success' => false, 'message' => '该账户已被禁用，请联系超级管理员', 'code' => 'ACCOUNT_DISABLED'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => '用户名或密码错误', 'code' => 'INVALID_CREDENTIALS'];
        }

        $updateQuery = "UPDATE " . $this->table_name . " SET last_login_at = NOW(), last_login_ip = :last_login_ip WHERE id = :id";
        $updateStmt = $this->conn->prepare($updateQuery);
        $updateStmt->bindParam(":last_login_ip", $ipAddress);
        $updateStmt->bindParam(":id", $user['id'], PDO::PARAM_INT);
        $updateStmt->execute();

        $sessionToken = $this->generateSessionToken($user['id']);

        return [
            'success' => true,
            'message' => '登录成功',
            'data' => [
                'user_id' => $user['id'],
                'username' => $user['username'],
                'display_name' => $user['display_name'],
                'role' => $user['role'],
                'token' => $sessionToken,
                'token_expires_at' => date('Y-m-d H:i:s', time() + self::SESSION_TTL)
            ]
        ];
    }

    private function generateSessionToken($userId) {
        $randomBytes = bin2hex(random_bytes(32));
        $timestamp = dechex(time());
        $signature = hash_hmac('sha256', $userId . '|' . $randomBytes . '|' . $timestamp, $this->getSecretKey());
        return base64_encode($userId . '.' . $randomBytes . '.' . $timestamp . '.' . $signature);
    }

    public function validateSessionToken($token) {
        try {
            $decoded = base64_decode($token, true);
            if (!$decoded) return false;

            $parts = explode('.', $decoded);
            if (count($parts) !== 4) return false;

            list($userId, $randomBytes, $timestamp, $signature) = $parts;
            $expectedSignature = hash_hmac('sha256', $userId . '|' . $randomBytes . '|' . $timestamp, $this->getSecretKey());

            if (!hash_equals($expectedSignature, $signature)) return false;

            $tokenTime = hexdec($timestamp);
            if ((time() - $tokenTime) > self::SESSION_TTL) return false;

            $user = $this->findById((int)$userId);
            if (!$user || !$user['is_active']) return false;

            return $user;
        } catch (Exception $e) {
            error_log("Session validation error: " . $e->getMessage());
            return false;
        }
    }

    private function getSecretKey() {
        $envKey = getenv('ADMIN_SECRET_KEY');
        if ($envKey) return $envKey;
        return 'MirrorRadar_Default_Secret_Key_Change_In_Production_2024';
    }

    public function verifyPassword($userId, $password) {
        $user = $this->findById($userId);
        if (!$user) return false;
        $fullUser = $this->findByUsername($user['username']);
        return $fullUser && password_verify($password, $fullUser['password_hash']);
    }
}
