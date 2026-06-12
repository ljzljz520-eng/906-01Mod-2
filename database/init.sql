-- 创建数据库
CREATE DATABASE IF NOT EXISTS torrent_search CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 授权给 baobei 用户
GRANT ALL PRIVILEGES ON torrent_search.* TO 'baobei'@'%';
FLUSH PRIVILEGES;

USE torrent_search;

-- 创建搜索历史表
CREATE TABLE IF NOT EXISTS search_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  keyword VARCHAR(50) NOT NULL COMMENT '搜索源',
  query VARCHAR(255) NOT NULL COMMENT '搜索关键词',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  INDEX idx_created_at (created_at),
  INDEX idx_keyword (keyword)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='搜索历史表';

-- 创建收藏表
CREATE TABLE IF NOT EXISTS favorites (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(500) NOT NULL COMMENT 'Torrent 名称',
  magnet TEXT NOT NULL COMMENT '磁力链接',
  size VARCHAR(50) COMMENT '文件大小',
  seeders INT DEFAULT 0 COMMENT '做种数',
  leechers INT DEFAULT 0 COMMENT '下载数',
  category VARCHAR(100) COMMENT '分类',
  source VARCHAR(50) COMMENT '来源站点',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  INDEX idx_created_at (created_at),
  INDEX idx_source (source),
  UNIQUE INDEX idx_magnet (magnet(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='收藏表';

-- 插入示例搜索历史数据
INSERT INTO search_history (keyword, query, created_at) VALUES
('1337x', 'Avengers', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
('yts', 'Inception', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('eztv', 'Breaking Bad', DATE_SUB(NOW(), INTERVAL 3 DAY)),
('1337x', 'The Matrix', DATE_SUB(NOW(), INTERVAL 5 DAY));

-- 插入示例收藏数据
INSERT INTO favorites (name, magnet, size, seeders, leechers, category, source, created_at) VALUES
(
  'Avengers: Endgame (2019) [1080p]',
  'magnet:?xt=urn:btih:EXAMPLE1234567890ABCDEF&dn=Avengers+Endgame',
  '2.5 GB',
  1250,
  85,
  'Movies',
  '1337x',
  DATE_SUB(NOW(), INTERVAL 1 DAY)
),
(
  'The Dark Knight (2008) [720p]',
  'magnet:?xt=urn:btih:EXAMPLE0987654321FEDCBA&dn=The+Dark+Knight',
  '1.8 GB',
  890,
  42,
  'Movies',
  'yts',
  DATE_SUB(NOW(), INTERVAL 3 DAY)
);

-- 创建开源资源表
CREATE TABLE IF NOT EXISTS resources (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(500) NOT NULL COMMENT '资源名称',
  description TEXT COMMENT '资源描述',
  original_url VARCHAR(1000) COMMENT '原始地址',
  category VARCHAR(100) COMMENT '分类',
  size VARCHAR(50) COMMENT '文件大小',
  checksum VARCHAR(128) COMMENT '原始校验值',
  checksum_type VARCHAR(20) DEFAULT 'SHA256' COMMENT '校验方式',
  original_updated_at TIMESTAMP NULL COMMENT '原始更新时间',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  INDEX idx_category (category),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='开源资源表';

-- 创建镜像同步表
CREATE TABLE IF NOT EXISTS mirror_sync (
  id INT AUTO_INCREMENT PRIMARY KEY,
  resource_id INT NOT NULL COMMENT '资源ID',
  mirror_type ENUM('github', 'gitee', 'enterprise', 'oss') NOT NULL COMMENT '镜像类型',
  mirror_name VARCHAR(100) COMMENT '镜像名称',
  mirror_url VARCHAR(1000) COMMENT '镜像地址',
  sync_status ENUM('synced', 'syncing', 'outdated', 'failed', 'unknown') DEFAULT 'unknown' COMMENT '同步状态',
  last_sync_time TIMESTAMP NULL COMMENT '最近同步时间',
  mirror_size VARCHAR(50) COMMENT '镜像文件大小',
  size_diff VARCHAR(50) COMMENT '大小差异（字节）',
  size_diff_percent DECIMAL(5,2) COMMENT '大小差异百分比',
  mirror_checksum VARCHAR(128) COMMENT '镜像校验值',
  checksum_match TINYINT(1) DEFAULT NULL COMMENT '校验值是否匹配',
  sync_lag_seconds INT DEFAULT NULL COMMENT '同步落后秒数',
  available TINYINT(1) DEFAULT 1 COMMENT '是否可用下载',
  last_check_time TIMESTAMP NULL COMMENT '最近检测时间',
  last_check_by VARCHAR(100) COMMENT '最近检测人',
  error_message TEXT COMMENT '错误信息',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  INDEX idx_resource_id (resource_id),
  INDEX idx_mirror_type (mirror_type),
  INDEX idx_sync_status (sync_status),
  INDEX idx_available (available),
  INDEX idx_last_sync_time (last_sync_time),
  CONSTRAINT fk_mirror_resource FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='镜像同步表';

-- 创建管理员操作日志表
CREATE TABLE IF NOT EXISTS admin_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_name VARCHAR(100) NOT NULL COMMENT '管理员名称',
  action_type ENUM('manual_check', 'toggle_available', 'update_mirror') NOT NULL COMMENT '操作类型',
  target_type VARCHAR(50) COMMENT '目标类型',
  target_id INT COMMENT '目标ID',
  details TEXT COMMENT '操作详情',
  ip_address VARCHAR(45) COMMENT 'IP地址',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '操作时间',
  INDEX idx_admin_name (admin_name),
  INDEX idx_action_type (action_type),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员操作日志表';

-- 插入示例资源数据
INSERT INTO resources (name, description, original_url, category, size, checksum, checksum_type, original_updated_at) VALUES
(
  'Linux Kernel 6.1.0 LTS',
  'Linux 内核长期支持版本',
  'https://www.kernel.org/pub/linux/kernel/v6.x/linux-6.1.tar.xz',
  '操作系统',
  '135 MB',
  'a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2',
  'SHA256',
  DATE_SUB(NOW(), INTERVAL 2 HOUR)
),
(
  'Node.js 20.10.0',
  'Node.js 运行时环境',
  'https://nodejs.org/dist/v20.10.0/node-v20.10.0-linux-x64.tar.xz',
  '开发工具',
  '45 MB',
  'b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3',
  'SHA256',
  DATE_SUB(NOW(), INTERVAL 30 MINUTE)
),
(
  'Python 3.12.0',
  'Python 编程语言',
  'https://www.python.org/ftp/python/3.12.0/Python-3.12.0.tar.xz',
  '开发工具',
  '22 MB',
  'c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4',
  'SHA256',
  DATE_SUB(NOW(), INTERVAL 1 DAY)
);

-- 插入示例镜像同步数据
INSERT INTO mirror_sync (resource_id, mirror_type, mirror_name, mirror_url, sync_status, last_sync_time, mirror_size, size_diff, size_diff_percent, mirror_checksum, checksum_match, sync_lag_seconds, available, last_check_time, last_check_by) VALUES
(1, 'github', 'GitHub 镜像', 'https://github.com/torvalds/linux', 'synced', DATE_SUB(NOW(), INTERVAL 1 HOUR), '135 MB', '0', 0.00, 'a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2', 1, 3600, 1, DATE_SUB(NOW(), INTERVAL 1 HOUR), 'system'),
(1, 'gitee', 'Gitee 镜像', 'https://gitee.com/mirrors/linux', 'outdated', DATE_SUB(NOW(), INTERVAL 3 DAY), '134 MB', '1048576', 0.75, 'd4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5', 0, 259200, 0, DATE_SUB(NOW(), INTERVAL 2 HOUR), 'admin'),
(1, 'enterprise', '企业镜像', 'https://mirrors.company.com/linux', 'synced', DATE_SUB(NOW(), INTERVAL 30 MINUTE), '135 MB', '0', 0.00, 'a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2', 1, 1800, 1, DATE_SUB(NOW(), INTERVAL 30 MINUTE), 'system'),
(2, 'github', 'GitHub 镜像', 'https://github.com/nodejs/node', 'synced', DATE_SUB(NOW(), INTERVAL 45 MINUTE), '45 MB', '0', 0.00, 'b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3', 1, 2700, 1, DATE_SUB(NOW(), INTERVAL 45 MINUTE), 'system'),
(2, 'oss', '对象存储备份', 'https://oss.example.com/nodejs/v20.10.0.tar.xz', 'failed', DATE_SUB(NOW(), INTERVAL 7 DAY), NULL, NULL, NULL, NULL, NULL, 604800, 0, DATE_SUB(NOW(), INTERVAL 1 HOUR), 'admin_zhang'),
(3, 'gitee', 'Gitee 镜像', 'https://gitee.com/mirrors/python', 'synced', DATE_SUB(NOW(), INTERVAL 2 HOUR), '22 MB', '0', 0.00, 'c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4', 1, 7200, 1, DATE_SUB(NOW(), INTERVAL 2 HOUR), 'system');

-- 插入示例管理员操作日志
INSERT INTO admin_logs (admin_name, action_type, target_type, target_id, details, ip_address, created_at) VALUES
('admin', 'manual_check', 'mirror', 2, '手动触发复测 Linux Kernel Gitee 镜像', '192.168.1.100', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
('admin_zhang', 'manual_check', 'mirror', 5, '手动触发复测 Node.js 对象存储备份', '10.0.0.25', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
('admin', 'toggle_available', 'mirror', 2, '将 Gitee 镜像标记为不可用', '192.168.1.100', DATE_SUB(NOW(), INTERVAL 1 HOUR));

-- 显示表结构
SHOW TABLES;
SELECT '✅ Database initialized successfully!' AS status;
