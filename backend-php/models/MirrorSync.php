<?php

class MirrorSync {
    private $conn;
    private $table_name = "mirror_sync";

    const OUTDATED_THRESHOLD = 86400;
    const FORCE_UNAVAILABLE_THRESHOLD = 259200;

    public $id;
    public $resource_id;
    public $mirror_type;
    public $mirror_name;
    public $mirror_url;
    public $sync_status;
    public $last_sync_time;
    public $mirror_size;
    public $size_diff;
    public $size_diff_percent;
    public $mirror_checksum;
    public $checksum_match;
    public $sync_lag_seconds;
    public $available;
    public $last_check_time;
    public $last_check_by;
    public $error_message;

    private $typeProfiles = [
        'github' => ['fail_rate' => 8, 'outdated_rate' => 12, 'max_lag_days' => 4, 'name' => 'GitHub'],
        'gitee' => ['fail_rate' => 5, 'outdated_rate' => 18, 'max_lag_days' => 5, 'name' => 'Gitee'],
        'enterprise' => ['fail_rate' => 3, 'outdated_rate' => 22, 'max_lag_days' => 7, 'name' => '企业镜像'],
        'oss' => ['fail_rate' => 10, 'outdated_rate' => 15, 'max_lag_days' => 6, 'name' => '对象存储备份']
    ];

    public function __construct($db) {
        $this->conn = $db;
    }

    public function findByResource($resourceId) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE resource_id = :resource_id ORDER BY 
            CASE mirror_type 
                WHEN 'github' THEN 1 
                WHEN 'gitee' THEN 2 
                WHEN 'enterprise' THEN 3 
                WHEN 'oss' THEN 4 
            END";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":resource_id", $resourceId, PDO::PARAM_INT);
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

    public function findAll($status = null) {
        $query = "SELECT m.*, r.name as resource_name, r.category as resource_category, r.size as original_size, r.checksum as original_checksum
            FROM " . $this->table_name . " m
            LEFT JOIN resources r ON m.resource_id = r.id";
        
        if ($status) {
            $query .= " WHERE m.sync_status = :status";
        }
        $query .= " ORDER BY m.updated_at DESC";
        
        $stmt = $this->conn->prepare($query);
        if ($status) {
            $stmt->bindParam(":status", $status);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getRadarSummary() {
        $query = "SELECT 
            mirror_type,
            COUNT(*) as total,
            SUM(CASE WHEN sync_status = 'synced' AND available = 1 THEN 1 ELSE 0 END) as healthy,
            SUM(CASE WHEN sync_status = 'outdated' THEN 1 ELSE 0 END) as outdated,
            SUM(CASE WHEN sync_status = 'failed' THEN 1 ELSE 0 END) as failed,
            SUM(CASE WHEN available = 0 THEN 1 ELSE 0 END) as unavailable
            FROM " . $this->table_name . "
            GROUP BY mirror_type";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET 
            resource_id=:resource_id,
            mirror_type=:mirror_type,
            mirror_name=:mirror_name,
            mirror_url=:mirror_url,
            sync_status=:sync_status,
            available=:available";
        
        $stmt = $this->conn->prepare($query);
        $this->sanitize();
        
        $stmt->bindParam(":resource_id", $this->resource_id, PDO::PARAM_INT);
        $stmt->bindParam(":mirror_type", $this->mirror_type);
        $stmt->bindParam(":mirror_name", $this->mirror_name);
        $stmt->bindParam(":mirror_url", $this->mirror_url);
        $stmt->bindParam(":sync_status", $this->sync_status);
        $stmt->bindValue(":available", $this->available ?? 1, PDO::PARAM_INT);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function update($id) {
        $query = "UPDATE " . $this->table_name . " SET 
            resource_id=:resource_id,
            mirror_type=:mirror_type,
            mirror_name=:mirror_name,
            mirror_url=:mirror_url,
            available=:available
            WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);
        $this->sanitize();
        
        $stmt->bindParam(":resource_id", $this->resource_id, PDO::PARAM_INT);
        $stmt->bindParam(":mirror_type", $this->mirror_type);
        $stmt->bindParam(":mirror_name", $this->mirror_name);
        $stmt->bindParam(":mirror_url", $this->mirror_url);
        $stmt->bindValue(":available", $this->available ?? 1, PDO::PARAM_INT);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function checkMirror($id, $adminName = 'system') {
        $resourceQuery = "SELECT r.* FROM resources r 
            INNER JOIN " . $this->table_name . " m ON r.id = m.resource_id 
            WHERE m.id = :id";
        $resourceStmt = $this->conn->prepare($resourceQuery);
        $resourceStmt->bindParam(":id", $id, PDO::PARAM_INT);
        $resourceStmt->execute();
        $resource = $resourceStmt->fetch();
        
        if (!$resource) {
            return ['success' => false, 'message' => '资源或镜像不存在'];
        }

        $mirror = $this->findOne($id);
        if (!$mirror) {
            return ['success' => false, 'message' => '镜像不存在'];
        }

        $result = $this->simulateMirrorCheck($resource, $mirror);

        $updateQuery = "UPDATE " . $this->table_name . " SET 
            sync_status=:sync_status,
            last_sync_time=:last_sync_time,
            mirror_size=:mirror_size,
            size_diff=:size_diff,
            size_diff_percent=:size_diff_percent,
            mirror_checksum=:mirror_checksum,
            checksum_match=:checksum_match,
            sync_lag_seconds=:sync_lag_seconds,
            available=:available,
            last_check_time=NOW(),
            last_check_by=:last_check_by,
            error_message=:error_message
            WHERE id=:id";

        $updateStmt = $this->conn->prepare($updateQuery);
        
        $updateStmt->bindParam(":sync_status", $result['sync_status']);
        $updateStmt->bindParam(":last_sync_time", $result['last_sync_time']);
        $updateStmt->bindParam(":mirror_size", $result['mirror_size']);
        $updateStmt->bindParam(":size_diff", $result['size_diff']);
        $updateStmt->bindParam(":size_diff_percent", $result['size_diff_percent']);
        $updateStmt->bindParam(":mirror_checksum", $result['mirror_checksum']);
        $updateStmt->bindValue(":checksum_match", $result['checksum_match'], PDO::PARAM_INT);
        $updateStmt->bindParam(":sync_lag_seconds", $result['sync_lag_seconds'], PDO::PARAM_INT);
        $updateStmt->bindValue(":available", $result['available'], PDO::PARAM_INT);
        $updateStmt->bindParam(":last_check_by", $adminName);
        $updateStmt->bindParam(":error_message", $result['error_message']);
        $updateStmt->bindParam(":id", $id, PDO::PARAM_INT);

        if ($updateStmt->execute()) {
            return ['success' => true, 'data' => $this->findOne($id)];
        }
        return ['success' => false, 'message' => '更新失败'];
    }

    private function simulateMirrorCheck($resource, $mirror) {
        $originalChecksum = $resource['checksum'];
        $originalSizeStr = $resource['size'];
        $originalUpdatedAt = $resource['original_updated_at'];

        $profile = $this->typeProfiles[$mirror['mirror_type']] ?? $this->typeProfiles['github'];
        $typeName = $profile['name'];

        $seed = hexdec(substr(md5($mirror['id'] . $resource['id'] . time()), 0, 8));
        $deterministicFactor = $seed % 100;

        $result = [
            'sync_status' => 'synced',
            'last_sync_time' => date('Y-m-d H:i:s'),
            'mirror_size' => $originalSizeStr,
            'size_diff' => '0',
            'size_diff_percent' => 0.00,
            'mirror_checksum' => $originalChecksum,
            'checksum_match' => 1,
            'sync_lag_seconds' => 0,
            'available' => 1,
            'error_message' => null
        ];

        if ($deterministicFactor < $profile['fail_rate']) {
            $errorMessages = [
                'github' => 'GitHub API 限流：请求过于频繁，请稍后重试',
                'gitee' => 'Gitee 仓库访问受限：权限不足或仓库已归档',
                'enterprise' => '企业镜像服务器连接超时：VPN 通道不稳定',
                'oss' => '对象存储访问失败：Bucket 权限配置错误或签名过期'
            ];

            $result['sync_status'] = 'failed';
            $result['mirror_size'] = null;
            $result['size_diff'] = null;
            $result['size_diff_percent'] = null;
            $result['mirror_checksum'] = null;
            $result['checksum_match'] = null;
            $result['available'] = 0;
            $result['error_message'] = $errorMessages[$mirror['mirror_type']] ?? "{$typeName}连接失败";
            $result['last_sync_time'] = $mirror['last_sync_time'] ?: date('Y-m-d H:i:s', strtotime('-7 days'));

            if ($originalUpdatedAt) {
                $lastSync = new DateTime($result['last_sync_time']);
                $origUpdate = new DateTime($originalUpdatedAt);
                $result['sync_lag_seconds'] = max(0, $origUpdate->getTimestamp() - $lastSync->getTimestamp());
            } else {
                $result['sync_lag_seconds'] = 604800;
            }
        } elseif ($deterministicFactor < ($profile['fail_rate'] + $profile['outdated_rate'])) {
            $maxLagDays = $profile['max_lag_days'];
            $lagDays = rand(1, $maxLagDays);
            $result['sync_lag_seconds'] = $lagDays * 86400 + rand(0, 86399);
            $result['last_sync_time'] = date('Y-m-d H:i:s', time() - $result['sync_lag_seconds']);

            $hasSizeDiff = (rand(1, 100) <= 70);
            if ($hasSizeDiff) {
                $diffBytes = rand(512 * 1024, 15 * 1024 * 1024);
                $result['size_diff'] = (string)$diffBytes;
                $origBytes = $this->parseSizeToBytes($originalSizeStr);
                if ($origBytes > 0) {
                    $result['size_diff_percent'] = round(($diffBytes / $origBytes) * 100, 2);
                } else {
                    $result['size_diff_percent'] = round(($diffBytes / (1024 * 1024 * 1024)) * 100, 2);
                }
                $result['mirror_size'] = $this->formatBytes($origBytes + $diffBytes);
            }

            $checksumMismatch = ($lagDays >= 2 && (rand(1, 100) <= 80)) || (rand(1, 100) <= 40);
            if ($checksumMismatch) {
                $result['checksum_match'] = 0;
                $result['mirror_checksum'] = substr(str_shuffle($originalChecksum), 0, strlen($originalChecksum));
            }

            if ($result['sync_lag_seconds'] >= self::FORCE_UNAVAILABLE_THRESHOLD) {
                $result['sync_status'] = 'outdated';
                $result['available'] = 0;
                $result['error_message'] = "{$typeName}同步已超过" . self::FORCE_UNAVAILABLE_THRESHOLD / 86400 . "天，为保护用户已自动禁用";
            } elseif ($result['sync_lag_seconds'] >= self::OUTDATED_THRESHOLD) {
                $result['sync_status'] = 'outdated';
                if ($result['checksum_match'] === 0 || $lagDays > 2) {
                    $result['available'] = 0;
                    $result['error_message'] = "{$typeName}同步落后超过24小时且校验不匹配，已自动禁用";
                }
            } else {
                $result['sync_status'] = 'outdated';
            }
        } else {
            $lagMinutes = rand(5, 240);
            $result['sync_lag_seconds'] = $lagMinutes * 60 + rand(0, 59);
            $result['last_sync_time'] = date('Y-m-d H:i:s', time() - $result['sync_lag_seconds']);

            $minorDiff = (rand(1, 100) <= 5);
            if ($minorDiff) {
                $diffBytes = rand(1024, 100 * 1024);
                $result['size_diff'] = (string)$diffBytes;
                $origBytes = $this->parseSizeToBytes($originalSizeStr);
                if ($origBytes > 0) {
                    $result['size_diff_percent'] = round(($diffBytes / $origBytes) * 100, 4);
                }
            }
        }

        if ($result['sync_lag_seconds'] > self::FORCE_UNAVAILABLE_THRESHOLD && $result['available'] != 0) {
            $result['available'] = 0;
            if (!$result['error_message']) {
                $result['error_message'] = '同步严重超时，已自动禁用下载';
            }
        }

        if ($result['checksum_match'] === 0 && $result['sync_status'] === 'outdated' && !$result['error_message']) {
            $result['error_message'] = '校验值与原始资源不匹配，建议等待同步完成';
        }

        return $result;
    }

    private function parseSizeToBytes($sizeStr) {
        if (!$sizeStr) return 0;
        $sizeStr = trim($sizeStr);
        $units = ['B' => 1, 'KB' => 1024, 'MB' => 1024 * 1024, 'GB' => 1024 * 1024 * 1024, 'TB' => 1024 * 1024 * 1024 * 1024];
        if (preg_match('/^([\d.]+)\s*(B|KB|MB|GB|TB)?$/i', $sizeStr, $matches)) {
            $num = (float)$matches[1];
            $unit = strtoupper($matches[2] ?? 'MB');
            return (int)($num * ($units[$unit] ?? $units['MB']));
        }
        return (int)$sizeStr;
    }

    private function formatBytes($bytes) {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes, 1024));
        return round($bytes / (1024 ** $i), 2) . ' ' . $units[$i];
    }

    public function toggleAvailable($id, $adminName) {
        $mirror = $this->findOne($id);
        if (!$mirror) {
            return ['success' => false, 'message' => '镜像不存在'];
        }

        $newAvailable = $mirror['available'] ? 0 : 1;

        $query = "UPDATE " . $this->table_name . " SET available = :available, last_check_time = NOW(), last_check_by = :last_check_by WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":available", $newAvailable, PDO::PARAM_INT);
        $stmt->bindParam(":last_check_by", $adminName);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return ['success' => true, 'available' => $newAvailable == 1];
        }
        return ['success' => false, 'message' => '操作失败'];
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    private function sanitize() {
        $this->resource_id = (int)$this->resource_id;
        $this->mirror_type = htmlspecialchars(strip_tags($this->mirror_type));
        $this->mirror_name = htmlspecialchars(strip_tags($this->mirror_name));
        $this->mirror_url = htmlspecialchars(strip_tags($this->mirror_url));
        $this->sync_status = htmlspecialchars(strip_tags($this->sync_status));
        $this->available = (int)($this->available ?? 1);
    }
}
