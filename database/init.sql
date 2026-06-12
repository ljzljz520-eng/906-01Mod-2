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

-- 显示表结构
SHOW TABLES;
SELECT '✅ Database initialized successfully!' AS status;
