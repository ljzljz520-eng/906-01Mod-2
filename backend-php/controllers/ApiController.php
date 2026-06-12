<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/SearchHistory.php';
require_once __DIR__ . '/../models/Favorite.php';
require_once __DIR__ . '/../models/Resource.php';
require_once __DIR__ . '/../models/MirrorSync.php';
require_once __DIR__ . '/../models/AdminLog.php';

class ApiController {
    private $db;
    private $searchHistory;
    private $favorite;
    private $resource;
    private $mirrorSync;
    private $adminLog;

    private $currentAdmin;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->searchHistory = new SearchHistory($this->db);
        $this->favorite = new Favorite($this->db);
        $this->resource = new Resource($this->db);
        $this->mirrorSync = new MirrorSync($this->db);
        $this->adminLog = new AdminLog($this->db);

        $this->currentAdmin = $_SERVER['HTTP_X_ADMIN_NAME'] ?? 'admin';
    }

    private function getClientIp() {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] 
            ?? $_SERVER['HTTP_X_REAL_IP'] 
            ?? $_SERVER['REMOTE_ADDR'] 
            ?? '127.0.0.1';
    }

    // GET /health
    public function healthCheck() {
        echo json_encode([
            'status' => 'ok',
            'timestamp' => date('c'),
            'service' => 'Torrent Search API (PHP)'
        ]);
    }

    // GET /api/providers
    public function getProviders() {
        echo json_encode([
            'success' => true,
            'data' => [
                'all' => ['1337x', 'Yts', 'ThePirateBay', 'Rarbg', 'Torrent9'],
                'active' => ['1337x', 'Yts']
            ]
        ]);
    }

    // GET /api/search/:keyword/:query/:page
    public function search($keyword, $query, $page = 1) {
        $this->searchHistory->keyword = $keyword;
        $this->searchHistory->query = $query;
        $this->searchHistory->create();

        $results = $this->generateDemoData($query, $keyword, $page);

        echo json_encode([
            'success' => true,
            'data' => $results,
            'meta' => [
                'keyword' => $keyword,
                'query' => $query,
                'page' => (int)$page,
                'count' => count($results),
                'demo' => true
            ]
        ]);
    }

    // GET /api/history
    public function getHistory() {
        $limit = isset($_GET['limit']) ? $_GET['limit'] : 20;
        $history = $this->searchHistory->findAll($limit);

        echo json_encode([
            'success' => true,
            'data' => $history
        ]);
    }

    // DELETE /api/history
    public function clearHistory() {
        if ($this->searchHistory->deleteAll()) {
            echo json_encode(['success' => true, 'message' => '搜索历史已清空']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '清空搜索历史失败']);
        }
    }

    // POST /api/favorites
    public function addFavorite() {
        $data = json_decode(file_get_contents("php://input"));

        if (!$data) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
            return;
        }

        if ($this->favorite->findOneByMagnet($data->magnet)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '该资源已在收藏列表中']);
            return;
        }

        $this->favorite->name = $data->name;
        $this->favorite->magnet = $data->magnet;
        $this->favorite->size = $data->size;
        $this->favorite->seeders = $data->seeders ?? 0;
        $this->favorite->leechers = $data->leechers ?? 0;
        $this->favorite->category = $data->category;
        $this->favorite->source = $data->source;

        if ($this->favorite->create()) {
            echo json_encode([
                'success' => true, 
                'message' => '收藏成功', 
                'data' => $this->favorite
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '收藏失败']);
        }
    }

    // GET /api/favorites
    public function getFavorites() {
        $favorites = $this->favorite->findAll();
        echo json_encode([
            'success' => true,
            'data' => $favorites
        ]);
    }

    // DELETE /api/favorites/:id
    public function deleteFavorite($id) {
        if ($this->favorite->delete($id)) {
            echo json_encode(['success' => true, 'message' => '删除成功']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => '收藏不存在']);
        }
    }

    // ==================== Resource APIs ====================

    // GET /api/resources
    public function getResources() {
        $category = $_GET['category'] ?? null;
        $resources = $this->resource->findAll($category);
        echo json_encode([
            'success' => true,
            'data' => $resources
        ]);
    }

    // GET /api/resources/:id
    public function getResource($id) {
        $resource = $this->resource->findOne($id);
        if (!$resource) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => '资源不存在']);
            return;
        }

        $mirrors = $this->mirrorSync->findByResource($id);
        $resource['mirrors'] = $mirrors;

        echo json_encode([
            'success' => true,
            'data' => $resource
        ]);
    }

    // GET /api/resources/search/:keyword
    public function searchResources($keyword) {
        $page = $_GET['page'] ?? 1;
        $perPage = $_GET['perPage'] ?? 10;
        $resources = $this->resource->search($keyword, $page, $perPage);
        echo json_encode([
            'success' => true,
            'data' => $resources
        ]);
    }

    // GET /api/resources/categories
    public function getResourceCategories() {
        $categories = $this->resource->getCategories();
        echo json_encode([
            'success' => true,
            'data' => $categories
        ]);
    }

    // ==================== Mirror Sync APIs ====================

    // GET /api/mirrors
    public function getMirrors() {
        $status = $_GET['status'] ?? null;
        $mirrors = $this->mirrorSync->findAll($status);
        echo json_encode([
            'success' => true,
            'data' => $mirrors
        ]);
    }

    // GET /api/mirrors/:id
    public function getMirror($id) {
        $mirror = $this->mirrorSync->findOne($id);
        if (!$mirror) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => '镜像不存在']);
            return;
        }
        echo json_encode([
            'success' => true,
            'data' => $mirror
        ]);
    }

    // POST /api/mirrors/:id/check
    public function checkMirror($id) {
        $data = json_decode(file_get_contents("php://input"), true);
        $adminName = $data['admin_name'] ?? $this->currentAdmin;

        $mirror = $this->mirrorSync->findOne($id);
        if (!$mirror) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => '镜像不存在']);
            return;
        }

        $result = $this->mirrorSync->checkMirror($id, $adminName);

        if ($result['success']) {
            $this->adminLog->logAction(
                $adminName,
                'manual_check',
                'mirror',
                $id,
                "手动触发复测镜像 #{$id} ({$mirror['mirror_name']})，结果：{$result['data']['sync_status']}",
                $this->getClientIp()
            );
            echo json_encode([
                'success' => true,
                'message' => '复测完成',
                'data' => $result['data'],
                'checked_by' => $adminName
            ]);
        } else {
            http_response_code(500);
            echo json_encode($result);
        }
    }

    // POST /api/resources/:id/check-all
    public function checkResourceMirrors($id) {
        $data = json_decode(file_get_contents("php://input"), true);
        $adminName = $data['admin_name'] ?? $this->currentAdmin;

        $resource = $this->resource->findOne($id);
        if (!$resource) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => '资源不存在']);
            return;
        }

        $mirrors = $this->mirrorSync->findByResource($id);
        $results = [];

        foreach ($mirrors as $mirror) {
            $checkResult = $this->mirrorSync->checkMirror($mirror['id'], $adminName);
            $results[] = [
                'mirror_id' => $mirror['id'],
                'mirror_name' => $mirror['mirror_name'],
                'success' => $checkResult['success'],
                'sync_status' => $checkResult['success'] ? $checkResult['data']['sync_status'] : 'error'
            ];
        }

        $this->adminLog->logAction(
            $adminName,
            'manual_check',
            'resource',
            $id,
            "批量复测资源「{$resource['name']}」的所有镜像，共" . count($results) . "个",
            $this->getClientIp()
        );

        echo json_encode([
            'success' => true,
            'message' => '批量复测完成',
            'data' => $results,
            'checked_by' => $adminName
        ]);
    }

    // POST /api/mirrors/:id/toggle-available
    public function toggleMirrorAvailable($id) {
        $data = json_decode(file_get_contents("php://input"), true);
        $adminName = $data['admin_name'] ?? $this->currentAdmin;

        $mirror = $this->mirrorSync->findOne($id);
        if (!$mirror) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => '镜像不存在']);
            return;
        }

        $result = $this->mirrorSync->toggleAvailable($id, $adminName);

        if ($result['success']) {
            $statusText = $result['available'] ? '可用' : '不可用';
            $this->adminLog->logAction(
                $adminName,
                'toggle_available',
                'mirror',
                $id,
                "将镜像 #{$id} ({$mirror['mirror_name']}) 标记为{$statusText}",
                $this->getClientIp()
            );
            echo json_encode([
                'success' => true,
                'message' => "镜像已标记为{$statusText}",
                'available' => $result['available'],
                'operated_by' => $adminName
            ]);
        } else {
            http_response_code(500);
            echo json_encode($result);
        }
    }

    // GET /api/mirrors/radar/summary
    public function getRadarSummary() {
        $summary = $this->mirrorSync->getRadarSummary();
        
        $totalMirrors = 0;
        $totalHealthy = 0;
        $totalOutdated = 0;
        $totalFailed = 0;
        $totalUnavailable = 0;

        foreach ($summary as $item) {
            $totalMirrors += $item['total'];
            $totalHealthy += $item['healthy'];
            $totalOutdated += $item['outdated'];
            $totalFailed += $item['failed'];
            $totalUnavailable += $item['unavailable'];
        }

        $healthRate = $totalMirrors > 0 ? round(($totalHealthy / $totalMirrors) * 100, 2) : 0;

        echo json_encode([
            'success' => true,
            'data' => [
                'by_type' => $summary,
                'totals' => [
                    'mirrors' => $totalMirrors,
                    'healthy' => $totalHealthy,
                    'outdated' => $totalOutdated,
                    'failed' => $totalFailed,
                    'unavailable' => $totalUnavailable,
                    'health_rate' => $healthRate
                ]
            ]
        ]);
    }

    // ==================== Admin Log APIs ====================

    // GET /api/admin/logs
    public function getAdminLogs() {
        $limit = $_GET['limit'] ?? 100;
        $adminName = $_GET['admin'] ?? null;
        $actionType = $_GET['action'] ?? null;

        $logs = $this->adminLog->findAll($limit, $adminName, $actionType);
        echo json_encode([
            'success' => true,
            'data' => $logs
        ]);
    }

    // GET /api/admin/logs/stats
    public function getAdminLogStats() {
        $stats = $this->adminLog->getStats();
        echo json_encode([
            'success' => true,
            'data' => $stats
        ]);
    }

    private function generateDemoData($query, $provider, $page) {
        $demoTorrents = [];
        
        $count = 5;
        for ($i = 0; $i < $count; $i++) {
            $randomString = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
            
            $item = [
                'Name' => "$query Result " . ($i + 1) . " [$provider] [1080p]",
                'Magnet' => "magnet:?xt=urn:btih:DEMO$randomString&dn=" . urlencode($query),
                'Size' => rand(1, 20) . "." . rand(0, 99) . " GB",
                'Seeders' => rand(50, 2000),
                'Leechers' => rand(10, 500),
                'Category' => 'Movies',
                'Url' => "https://example.com/torrent/" . strtolower(str_replace(' ', '-', $query)) . "-$i",
                'DateUploaded' => rand(1, 30) . ' days ago'
            ];
            $demoTorrents[] = $item;
        }
        return $demoTorrents;
    }
}
